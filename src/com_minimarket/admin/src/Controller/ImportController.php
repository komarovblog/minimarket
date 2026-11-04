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
    public function example(): void
    {
        Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));
        $this->assertCanImport();
        $name = $this->input->getCmd('template', '');
        $examples = [
            'catalog' => 'import-catalog-v1.json',
            'empty' => 'import-empty-v1.json',
        ];
        if (!isset($examples[$name])) {
            throw new \RuntimeException('Unknown MiniMarket example', 404);
        }
        $path = dirname(__DIR__, 2) . '/examples/' . $examples[$name];
        if (!is_file($path)) {
            throw new \RuntimeException('Missing MiniMarket example file', 404);
        }
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->setHeader('Content-Disposition',
            'attachment; filename="' . $examples[$name] . '"', true);
        $app->setHeader('X-Content-Type-Options', 'nosniff', true);
        $app->setHeader('Cache-Control', 'private, no-store, max-age=0', true);
        $app->sendHeaders();
        readfile($path);
        $app->close();
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
                || !str_ends_with(strtolower((string) ($file['name'] ?? '')), '.json')) {
                throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_FILE_INVALID');
            }
            $raw = file_get_contents($file['tmp_name']);
            if (!is_string($raw)) throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_FILE_INVALID');
            $doc = CatalogImport::decode($raw);
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $report = CatalogImport::run($db, $doc, false);
            $app->setUserState(self::SESSION_KEY, ['raw' => $raw, 'report' => $report]);
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
        if ($error instanceof \InvalidArgumentException
            && str_starts_with($error->getMessage(), 'COM_MINIMARKET_IMPORT_')) {
            $parts = explode(': ', $error->getMessage(), 2);
            return Text::_($parts[0]) .
                (isset($parts[1]) ? ' (' . htmlspecialchars($parts[1], ENT_QUOTES, 'UTF-8') . ')' : '');
        }
        return Text::_('COM_MINIMARKET_IMPORT_DATABASE_ERROR');
    }
}
