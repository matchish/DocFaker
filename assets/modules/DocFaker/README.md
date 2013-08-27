DocFaker
---------------

DokFaker - модуль MODx Evolution с помощью которого можно создавать документы с уже заполненными полями(по определенным шаблонам) включая TV. Можно генерировать дерево документов разного уровня вложенности.

DocFaker требует PHP >= 5.3.3.

Установка.
--------------
  1. Копируем папку DocFaker в /assets/modules/
  2. Создаем модуль с именем DocFaker и содержимым
<code>include_once(MODX_BASE_PATH.'assets/modules/DocFaker/src/DocFaker.module.php');</code>

Зависимости.
--------------
  1. [Faker](https://github.com/fzaninotto/Faker "Faker")
  2. [MODxAPI](https://github.com/AgelxNash/resourse "MODxAPI")

Обсуждение модуля и инструкция по использованию [здесь](http://modx.im/blog/addons/1118.html "MODx.im").
