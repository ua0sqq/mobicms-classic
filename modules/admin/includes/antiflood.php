<?php
/**
 * mobiCMS (https://mobicms.org/)
 * This file is part of mobiCMS Content Management System.
 *
 * @license     https://opensource.org/licenses/GPL-3.0 GPL-3.0 (see the LICENSE.md file)
 * @link        http://mobicms.org mobiCMS Project
 * @copyright   Copyright (C) mobiCMS Community
 */

defined('MOBICMS') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);
// Проверяем права доступа

/** @var Mobicms\Api\UserInterface $systemUser */
$systemUser = $container->get(Mobicms\Api\UserInterface::class);

$config = $container->get('config')['mobicms'];

if ($systemUser->rights < 7) {
    exit(_t('Access denied'));
}

$set_af = $config['antiflood'];
echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Antiflood Settings') . '</div>';

if (isset($_POST['submit']) || isset($_POST['save'])) {
    // Принимаем данные из формы
    $set_af['mode'] = isset($_POST['mode']) && $_POST['mode'] > 0 && $_POST['mode'] < 5 ? intval($_POST['mode']) : 1;
    $set_af['day'] = isset($_POST['day']) ? intval($_POST['day']) : 10;
    $set_af['night'] = isset($_POST['night']) ? intval($_POST['night']) : 30;
    $set_af['dayfrom'] = isset($_POST['dayfrom']) ? intval($_POST['dayfrom']) : 10;
    $set_af['dayto'] = isset($_POST['dayto']) ? intval($_POST['dayto']) : 22;

    // Проверяем правильность ввода данных
    if ($set_af['day'] < 4) {
        $set_af['day'] = 4;
    }

    if ($set_af['day'] > 300) {
        $set_af['day'] = 300;
    }

    if ($set_af['night'] < 4) {
        $set_af['night'] = 4;
    }

    if ($set_af['night'] > 300) {
        $set_af['night'] = 300;
    }

    if ($set_af['dayfrom'] < 6) {
        $set_af['dayfrom'] = 6;
    }

    if ($set_af['dayfrom'] > 12) {
        $set_af['dayfrom'] = 12;
    }

    if ($set_af['dayto'] < 17) {
        $set_af['dayto'] = 17;
    }

    if ($set_af['dayto'] > 23) {
        $set_af['dayto'] = 23;
    }

    $config['antiflood'] = $set_af;
    $configFile = "<?php\n\n" . 'return ' . var_export(['mobicms' => $config], true) . ";\n";

    if (!file_put_contents(ROOT_PATH . 'system/config/autoload/system.local.php', $configFile)) {
        echo 'ERROR: Can not write system.local.php</body></html>';
        exit;
    }

    echo '<div class="rmenu">' . _t('Settings are saved successfully') . '</div>';

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

// Форма ввода параметров Антифлуда
echo '<form action="index.php?act=antiflood" method="post">'
    . '<div class="gmenu"><p><h3>' . _t('Operation mode') . '</h3><table cellspacing="2">'
    . '<tr><td valign="top"><input type="radio" name="mode" value="3" ' . ($set_af['mode'] == 3 ? 'checked="checked"' : '') . '/></td><td>' . _t('Day') . '</td></tr>'
    . '<tr><td valign="top"><input type="radio" name="mode" value="4" ' . ($set_af['mode'] == 4 ? 'checked="checked"' : '') . '/></td><td>' . _t('Night') . '</td></tr>'
    . '<tr><td valign="top"><input type="radio" name="mode" value="2" ' . ($set_af['mode'] == 2 ? 'checked="checked"' : '') . '/></td><td>' . _t('Day') . ' / ' . _t('Night') . '<br><small>'
    . _t('Automatic change from day to night mode, according to specified time set')
    . '</small></td></tr>'
    . '<tr><td valign="top"><input type="radio" name="mode" value="1" ' . ($set_af['mode'] == 1 ? 'checked="checked"' : '') . '/></td><td>' . _t('Adaptive') . '<br><small>'
    . _t('If one of administration is online (on the site), the system work in &quot;day&quot; mode, if administration is offline, it switch to &quot;night&quot;') . '</small></td></tr>'
    . '</table></p></div>'
    . '<div class="menu"><p><h3>' . _t('Time limit') . '</h3>'
    . '<input name="day" size="3" value="' . $set_af['day'] . '" maxlength="3" />&#160;' . _t('Day') . '<br>'
    . '<input name="night" size="3" value="' . $set_af['night'] . '" maxlength="3" />&#160;' . _t('Night')
    . '<br><small>' . _t('Min. 4, max. 300 seconds') . '</small></p>'
    . '<p><h3>' . _t('Day mode') . '</h3>'
    . '<input name="dayfrom" size="2" value="' . $set_af['dayfrom'] . '" maxlength="2" style="text-align:right"/>:00&#160;' . _t('Beginning of day') . ' <span class="gray">(6-12)</span><br>'
    . '<input name="dayto" size="2" value="' . $set_af['dayto'] . '" maxlength="2" style="text-align:right"/>:00&#160;' . _t('End of day') . ' <span class="gray">(17-23)</span>'
    . '</p><p><br><input type="submit" name="submit" value="' . _t('Save') . '"/></p></div></form>'
    . '<div class="phdr"><a href="?">' . _t('Back') . '</a></div>';
