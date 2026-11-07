# Совместимость с Blank и другими шаблонами Joomla 6

**Дата аудита:** 2026-11-07. **Тип:** документ с требованиями / проверками, НЕ релиз MiniMarket. Код не изменён.

## Главный принцип
MiniMarket сам отвечает за отображение списка и сетки товаров, количество колонок, фильтры, варианты, наличие, SKU, цены, корзину и заказ. Blank/другой Joomla-шаблон отвечает только за внешний контейнер, header/footer и позиции модулей.

**Запрещено дублировать responsive product column count в Blank.**

## Фактический код development
- `src/com_minimarket/site/src/Service/CategoryDisplayOptions.php`:
  `resolve()` наследует глобальные значения или использует override конкретной категории.
  `bootstrapGridClasses()` формирует `row row-cols-N row-cols-md-N row-cols-lg-N row-cols-xl-N g-3`.
- `src/com_minimarket/admin/config.xml`: глобальные `category_cards_mobile/tablet/desktop/wide` по умолчанию `1/2/3/4`.
- `src/com_minimarket/admin/forms/category.xml`: `cards_mobile/tablet/desktop/wide`, `-1` означает наследование.
- `src/com_minimarket/site/tmpl/category/default.php`: вывод `row-cols-*` и `<li class="col">` внутри категории.
- `src/com_minimarket/media/css/catalog-layout.css`: содержит собственные правила карточек/фильтров, **не содержит** базовую реализацию `row-cols-*`.
- `src/com_minimarket/site/src/View/Category/HtmlView.php`: регистрирует `catalog-layout.css`, badges, JS фасетных фильтров, но в проверенном участке нет явного подключения Bootstrap grid CSS.

## Выявленный риск для Blank
`Blank 0.1.3` умышленно не загружает полный Bootstrap CSS (только минимальную версию container CSS). Поэтому при использовании MiniMarket с Blank классы `row-cols-*` **могут не работать**, если Bootstrap CSS не подключён другим источником. Это **кодовый риск**, не подтверждённая runtime-ошибка: совместный frontend MiniMarket+Blank не тестировали.

## Предлагаемая задача для MiniMarket (отдельная, без изменения текущего планового релиза)
- [ ] **MM-BLANK-01**: автономность CSS сетки каталога от выбранного Joomla-шаблона.
- [ ] Выбрать один путь: минимальный локальный CSS для `.com-minimarket-category .mm-category-products` и детей `.col` c применением конфигурации из `CategoryDisplayOptions`; либо явная WAM-зависимость от штатного Bootstrap grid ресурса (если доступна и не создаёт дублирования).
- [ ] Не добавлять глобальные `.row`, `.col`, `.row-cols-*` стили, которые испортят другие Joomla-компоненты.
- [ ] Сохранить глобальное наследование категорией и действующие 4 уровня: mobile=XS+SM (до 768), tablet=MD (от 768), desktop=LG (от 992), wide=XL+XXL (от 1200).
- [ ] Изучить переход на Container Queries/защиту minimum card width, если outer Blank sidebar + MiniMarket filter sidebar сужают содержимое. Числа колонок — целевые/максимальные; избегать горизонтального переполнения.
- [ ] Прогнать responsive 375/576/768/992/1200/1400, список/сетку, глобальные и категорийные overrides, sidebar filters on/off, наличие/опции/корзину и frontend под Blank и Cassiopeia.
- [ ] Собирать отдельный ZIP MiniMarket только после реального изменения его кода и отдельного smoke/regression теста. Не менять текущий релиз ради одной документационной заметки.

## CSS/JS contract
- Blank classes: `.blank-*` / variables `--blank-*`; владеет внешней оболочкой.
- MiniMarket: `.com-minimarket-*`, `.mm-*` / variables `--mm-*`, data attributes `data-mm-*`; владеет внутренним UI и функциональностью.
- Визуальные пресеты Blank не должны тайно менять число карточек или ход фильтров; Joomla overrides для MiniMarket допускаются только для отображения, не бизнес-логики.
- Должна работать установка MiniMarket на обычную Cassiopeia и Blank без обязательного выбора конкретного шаблона.

## Связанный документ Blank
https://github.com/komarovblog/blank/blob/development/docs/integrations/minimarket.md
