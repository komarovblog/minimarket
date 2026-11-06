<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CatalogImport;
use MiniMarket\Component\Minimarket\Administrator\Service\CatalogTabularV1;
use MiniMarket\Component\Minimarket\Administrator\Service\CatalogExportV1;

/** Two-step upload → preview → apply, bound to the current administrator session. */
final class ImportController extends BaseController
{
    private const SESSION_KEY = 'com_minimarket.import.pending';
    private const MAX_UPLOAD = 3145728;

    private function assertCanImport(): void
    {
        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.create', 'com_minimarket')
            || !$user->authorise('core.edit', 'com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }

    /**
     * Download shipped examples without requiring GitHub or public file URLs.
     * This is a GET-only read guarded by the Joomla admin ACL and CSRF token.
     */
    private function sendDownload(string $content, string $filename, string $type): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', $type, true);
        $app->setHeader('Content-Disposition',
            'attachment; filename="' . $filename . '"', true);
        $app->setHeader('X-Content-Type-Options', 'nosniff', true);
        $app->setHeader('Cache-Control', 'private, no-store, max-age=0', true);
        $app->sendHeaders();
        echo $content;
        $app->close();
    }

    private static function renderFormat(array $doc, string $format): array
    {
        return match ($format) {
            'json'=>[json_encode($doc, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
                'application/json; charset=utf-8'],
            'csv'=>[CatalogTabularV1::csv($doc), 'text/csv; charset=utf-8'],
            'xlsx'=>[CatalogTabularV1::xlsx($doc),
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            default=>throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_FILE_INVALID'),
        };
    }

    /**
     * Download bundled empty or filled template in JSON, CSV or XLSX.
     * Never fetch template files from GitHub or external network locations.
     */
    public function example(): void
    {
        Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));
        $this->assertCanImport();
        $name = $this->input->getCmd('template', '');
        $format = $this->input->getCmd('filetype', 'json');
        $examples = ['catalog'=>'import-catalog-v1.json',
            'rich'=>'import-rich-catalog-v1.json',
            'empty'=>'import-empty-v1.json'];
        if (!isset($examples[$name]) || !in_array($format,['json','csv','xlsx'],true)) {
            throw new \RuntimeException('Unknown MiniMarket example', 404);
        }
        $path = dirname(__DIR__, 2) . '/examples/' . $examples[$name];
        if (!is_file($path)) throw new \RuntimeException('Missing MiniMarket example file', 404);
        $doc = CatalogImport::decode((string)file_get_contents($path));
        [$bytes,$mime] = self::renderFormat($doc,$format);
        $this->sendDownload($bytes, 'minimarket-'. $name .'-template.'.$format, $mime);
    }

    /**
     * Export ONLY data described by CatalogImport v1. Data unsupported by v1
     * (modern SKU combinations, media, SEO) is not asserted to be backed up.
     */
    public function export(): void
    {
        Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));
        $this->assertCanImport();
        $format = $this->input->getCmd('filetype', 'json');
        if (!in_array($format,['json','csv','xlsx'],true)) {
            throw new \RuntimeException('Invalid export format', 400);
        }
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $doc = CatalogExportV1::document($db);
            [$bytes,$mime]=self::renderFormat($doc,$format);
            $this->sendDownload($bytes, 'minimarket-basic-catalog.'.$format, $mime);
        } catch (\Throwable $error) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_EXPORT_FAILED') . ': ' .
                self::friendlyError($error), 'error');
            $this->setRedirect(Route::_('index.php?option=com_minimarket&view=import', false));
        }
    }

    public function preview(): void
    {
        Session::checkToken('post') or die(Text::_('JINVALID_TOKEN'));
        $this->assertCanImport();
        $app = Factory::getApplication();
        $app->setUserState(self::SESSION_KEY, null);
        try {
            $file = $app->input->files->get('package');
            if (!is_array($file)
                || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                || !is_string($file['tmp_name'] ?? null)
                || !is_uploaded_file($file['tmp_name'])
                || !is_int($file['size'] ?? null)
                || $file['size'] < 1 || $file['size'] > self::MAX_UPLOAD
                || !in_array(strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)),
                    ['json','csv','xlsx'], true)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_FILE_INVALID');
            }
            $raw = file_get_contents($file['tmp_name']);
            if (!is_string($raw)) throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_FILE_INVALID');
            $format = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
            $doc = match ($format) {
                'json' => CatalogImport::decode($raw),
                'csv' => CatalogImport::decode(json_encode(
                    CatalogTabularV1::parseCsv($raw),
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)),
                'xlsx' => CatalogImport::decode(json_encode(
                    CatalogTabularV1::parseXlsx($raw),
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)),
            };
            // Session contains one canonical, validated package independently
            // of the originally uploaded transport format.
            $canonical = json_encode($doc, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $report = CatalogImport::run($db, $doc, false);
            $app->setUserState(self::SESSION_KEY, ['raw' => $canonical, 'report' => $report]);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_IMPORT_PREVIEW_OK'), 'success');
        } catch (\Throwable $error) {
            $app->enqueueMessage(Text::_('COM_MINIMARKET_IMPORT_PREVIEW_FAILED') . ': ' .
                self::friendlyError($error), 'error');
        }
        $this->setRedirect(Route::_('index.php?option=com_minimarket&view=import', false));
    }

    public function apply(): void
    {
        Session::checkToken('post') or die(Text::_('JINVALID_TOKEN'));
        $this->assertCanImport();
        $app = Factory::getApplication();
        $pending = $app->getUserState(self::SESSION_KEY);
        if (!is_array($pending) || !is_string($pending['raw'] ?? null)) {
            $app->enqueueMessage(Text::_('COM_MINIMARKET_IMPORT_PREVIEW_REQUIRED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_minimarket&view=import', false));
            return;
        }
        // Apply the same exact bytes previewed. No file reupload and no hidden
        // client-supplied hash; source identity lives in the session.
        try {
            $doc = CatalogImport::decode($pending['raw']);
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $report = CatalogImport::run($db, $doc, true);
            $app->setUserState(self::SESSION_KEY, null);
            $app->setUserState('com_minimarket.import.last', $report);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_IMPORT_APPLIED'), 'success');
        } catch (\Throwable $error) {
            // If the database changed after preview, leave preview available
            // for investigation; failed transaction did not commit partial data.
            $app->enqueueMessage(Text::_('COM_MINIMARKET_IMPORT_APPLY_FAILED') . ': ' .
                self::friendlyError($error), 'error');
        }
        $this->setRedirect(Route::_('index.php?option=com_minimarket&view=import', false));
    }

    public function cancel(): void
    {
        Session::checkToken('post') or die(Text::_('JINVALID_TOKEN'));
        $this->assertCanImport();
        Factory::getApplication()->setUserState(self::SESSION_KEY, null);
        $this->setRedirect(Route::_('index.php?option=com_minimarket&view=import', false));
    }

    private static function friendlyError(\Throwable $error): string
    {
        // Do not leak SQL queries, schema names or filesystem paths to web UI.
        if (($error instanceof \InvalidArgumentException
                || $error instanceof \RuntimeException)
            && (str_starts_with($error->getMessage(), 'COM_MINIMARKET_IMPORT_')
                || str_starts_with($error->getMessage(), 'COM_MINIMARKET_EXPORT_')
                || str_starts_with($error->getMessage(), 'COM_MINIMARKET_EXCHANGE_'))) {
            $parts = explode(': ', $error->getMessage(), 2);
            return Text::_($parts[0]) .
                (isset($parts[1]) ? ' (' . htmlspecialchars($parts[1], ENT_QUOTES, 'UTF-8') . ')' : '');
        }
        return Text::_('COM_MINIMARKET_IMPORT_DATABASE_ERROR');
    }
}
