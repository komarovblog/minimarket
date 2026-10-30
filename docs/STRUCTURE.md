# Структура MiniMarket: Joomla, исходники и хостинг

Актуально: 2026-10-29. Только репозиторий `komarovblog/minimarket`, ветка `development`. Shop на Symfony — независимый проект.

## Актуальный путь в Open Server Panel 6.5.1

**С 30.10.2026 используется только стандартный каталог OSP `C:\OSPanel\home`.**
Рабочий Git-клон и проект: `C:\OSPanel\home\minimarket.local`; веб-корень `C:\OSPanel\home\minimarket.local\public`; домен `http://minimarket.local`. Владелец проекта MiniMarket — `komarovblog/minimarket`, ветка `development`. Предыдущее размещение в `C:\Users\User\Desktop\WebProjects\MiniMarket` устарело; прежнюю папку не удалять автоматически, так как в ней могут быть локальные файлы. OSP больше не ищет там проекты.

## Главный принцип

**В `public` находится ВЕСЬ РАБОТАЮЩИЙ САЙТ Joomla 6**: ядро, админка, установленные компоненты, модули, плагины, шаблоны, медиафайлы и обычный `index.php`. **Папка `public` по назначению соответствует `public_html` на обычном хостинге**. Содержимое `public` переносится в `public_html`.

**За пределами `public` находятся исходники и инструменты разработчика**, которые веб-сервер не должен публиковать: `.git`, `src`, `scripts`, `docs`, `.osp`, `.env`. Они нужны для разработки и сборки, но НЕ являются частью сайта на хостинге.

## Структура локально ДО установки Joomla

```text
C:\OSPanel\home\minimarket.local\
├── .git\
├── .osp\
│   └── project.ini           # OSP 6.5.1: Apache, PHP-8.5, web_root = {base_dir}\public
├── public\
│   └── .gitkeep              # Содержимое Joomla пока отсутствует
├── src\
│   ├── README.md
│   └── com_minimarket\
│       └── README.md         # Заготовка проекта собственного компонента
├── scripts\
│   └── setup-local.ps1       # Только временный локальный PHP-smoke-test
├── docs\
├── .gitignore
├── README.md
├── NEW_AGENT.md
└── CURRENT_TASK.md
```

## Структура локально ПОСЛЕ чистой установки Joomla и пакета MiniMarket

```text
MiniMarket\
├── .git\                    # ТОЛЬКО ДЛЯ РАЗРАБОТКИ
├── .osp\                    # ТОЛЬКО OPEN SERVER
├── src\                     # ИСХОДНИКИ com_minimarket (GitHub)
├── scripts\                 # СБОРКА / ПРОВЕРКИ (GitHub)
├── docs\                    # ДОКУМЕНТАЦИЯ (GitHub)
└── public\                  # ВЕСЬ РАБОТАЮЩИЙ САЙТ = public_html
    ├── index.php             # ОРИГИНАЛЬНЫЙ index.php Joomla
    ├── administrator\        # Административная часть Joomla
    │   └── components\
    │       └── com_minimarket\  # УСТАНОВЛЕННЫЙ компонент (после разработки)
    ├── components\
    │   └── com_minimarket\  # Публичная часть УСТАНОВЛЕННОГО компонента
    ├── modules\             # УСТАНОВЛЕННЫЕ модули MiniMarket (при необходимости)
    ├── plugins\             # УСТАНОВЛЕННЫЕ плагины (при необходимости)
    ├── media\
    │   └── com_minimarket\  # Статические ресурсы компонента, если используются
    ├── templates\
    ├── libraries\
    ├── includes\
    └── configuration.php     # ЛОКАЛЬНЫЕ секреты Joomla, НЕ В GITHUB
```

Все показанные вложенные каталоги Joomla создает **официальный Full Package** и менеджер расширений; не создавать их пустыми вручную и не добавлять оригинальные файлы Joomla в GitHub. Фактический перечень директорий зависит от версии Joomla и состава расширений.

`src/com_minimarket` и `public/components/com_minimarket` **не два независимых проекта**: первый — редактируемые исходники, второй — их установленная Joomla-копия. После сборки ZIP Joomla штатно устанавливает файлы в `components`, `administrator/components` и другие необходимые места. Никогда не редактировать установленные копии как единственный источник изменений.

## Отображение на хостинг

| Локальный путь | На обычном хостинге | Как переносим |
| --- | --- | --- |
| `MiniMarket/public/` **содержимое** | `public_html/` | Оригинальные файлы установленной Joomla и сайта; при первоначальном переносе файловую копию и отдельно БД |
| `MiniMarket/src/com_minimarket/` | Не публикуем как каталог | Собираем ZIP и устанавливаем/обновляем через Joomla |
| `MiniMarket/.git/`, `docs/`, `scripts/`, `.osp/` | Не переносим в `public_html` | Только локальная разработка и GitHub |
| База MySQL `minimarket` | Отдельная БД на хостинге | Отдельный защищённый перенос/резервная копия, не Git |
| `public/configuration.php` | `public_html/configuration.php` | Настройки окружения задаются отдельно; не коммитить секреты |

Не копировать папку `public` как дополнительный уровень (`public_html/public`): сайт должен открываться непосредственно по домену, а корнем будет **содержимое** `public`.

На стандартном хостинге папка `public_html` может лежать внутри пользовательского каталога, а Open Server использует `{base_dir}\public`. В обоих случаях веб-сервер обслуживает только дерево рабочего сайта.

## Порядок первого развёртывания
1. Сохранить старый локальный MiniMarket как резервную копию вне `WebProjects`; получить свежую `development` в `WebProjects\MiniMarket` по `CURRENT_TASK.md`. Не удалять без бэкапа.
2. Проверить профиль `.osp/project.ini` и локальный `index.php` через `setup-local.ps1` и `curl`.
3. В phpMyAdmin создать отдельную БД `minimarket` и собственного пользователя MySQL.
4. Установить **официальный Joomla 6 Full Package** прямо в `MiniMarket/public`: штатный `index.php` Joomla должен заменить временный тестовый файл. Joomla ставится и использует СВОИ системные таблицы.
5. После проверки Joomla начать `src/com_minimarket`; собирать пакет, обновлять через интерфейс Joomla; SQL-миграции затрагивают только `#__minimarket_*` в той же базе.
6. Не менять файлы ядра Joomla. При обновлении Joomla проверять совместимость своего пакета на отдельной staging-копии.

## Git-правила
- GitHub хранит нашу разработку: `src`, `scripts`, `docs`, настройки проекта `.osp/project.ini` без секретов, заглушку `public/.gitkeep`. Рабочая ветка `development`.
- По `.gitignore` **всё остальное содержимое `public` не отслеживается**, поскольку это развёрнутая Joomla и данные сайта.
- `main` и чужие проекты не затрагивать. Не выполнять опасные очистки или force-push. Резервные копии сайта и MySQL хранятся отдельно.
