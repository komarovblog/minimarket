# MiniMarket — Joomla 6 extension

**Repository:** `komarovblog/minimarket`; work only in `development`. This is **not** the separate Symfony Shop project.

## Directory layout

```text
MiniMarket/
├── .osp/project.ini       Open Server Panel 6.5.1; Apache / PHP 8.5 / HTTP; tracked
├── public/.gitkeep        Local Joomla 6 web root; Joomla files NOT tracked
├── src/                   MiniMarket component/modules source code (later)
├── scripts/setup-local.ps1
├── docs/                  Requirements and development plan
├── NEW_AGENT.md
└── CURRENT_TASK.md
```

This checkout intentionally **does not include Joomla core**. Install the original Joomla Full Package later under `public`, and its MySQL database separately. Extensions are developed in `src`, built as Joomla packages and installed/updated using Joomla's extension manager. Never modify Joomla core.

## First local launch on Windows / Open Server 6.5.1

After cloning **branch `development`** into:

```text
C:\Users\User\Desktop\WebProjects\MiniMarket
```

In **CMD** (including the Open Server console), run:

```cmd
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "C:\Users\User\Desktop\WebProjects\MiniMarket\scripts\setup-local.ps1"
```

This script writes `public/index.php` **only if it does not already exist**. It is a disposable *local-only* PHP check page and is ignored by Git. It must be replaced by Joomla's original `index.php` when installing Joomla. The script never downloads or modifies Joomla core.

In Open Server, restart, verify that MiniMarket has HTTP **Apache** and PHP **8.5** selected and its project modules are enabled. Browse to `http://minimarket.local/` or test:

```cmd
curl.exe -4 --noproxy "*" --max-time 10 http://minimarket.local/
```

Expected before Joomla: `MINIMARKET_BOOTSTRAP_OK`, a `PHP_VERSION=` line and `JOOMLA_NOT_INSTALLED`. If 503 persists, inspect the project's actual server config and error log; a placeholder alone is not proof of the previous failure's cause.

## Reset an earlier broken local checkout safely

**Never blindly delete the old MiniMarket folder:** it may contain uncommitted work, `.env`, OSP settings or other local files. Preserve it as a dated backup **outside** `WebProjects`, then clone fresh from GitHub to the original location. Do not touch other projects, Open Server's system folder, MySQL databases or the unrelated Shop repository.

Use the step-by-step instructions in `CURRENT_TASK.md`. After the clean environment is confirmed, prepare the separate MySQL `minimarket` database and install Joomla through its official installer.

## Git rules

`development` only, `main` untouched. Never commit `public` Joomla files, secret config, `.env` or database dumps. `.osp/project.ini` is shared, contains no passwords; if OSP changes it through its UI, review the diff before committing. Avoid `git reset --hard`, force pushes and destructive cleanup.
