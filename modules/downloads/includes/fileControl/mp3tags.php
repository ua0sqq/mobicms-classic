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

/** @var Mobicms\Api\UserInterface $systemUser */
$systemUser = $container->get(Mobicms\Api\UserInterface::class);

/** @var League\Plates\Engine $view */
$view = $container->get(League\Plates\Engine::class);

ob_start();

require dirname(dirname(__DIR__)) . '/classes/download.php';
require dirname(dirname(__DIR__)) . '/classes/getid3/getid3.php';

// Редактировать mp3 тегов
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name']) || pathinfo($res_down['name'], PATHINFO_EXTENSION) != 'mp3' || $systemUser->rights < 6) {
    echo '<a href="?">' . _t('Downloads') . '</a>';
    echo $view->render('system::app/legacy', [
        'title'   => _t('Downloads'),
        'content' => ob_get_clean(),
    ]);
    exit;
}

echo '<div class="phdr"><b>' . _t('Edit MP3 Tags') . ':</b> ' . htmlspecialchars($res_down['rus_name']) . '</div>';

$getID3 = new getID3;
$getID3->encoding = 'cp1251';
$getid = $getID3->analyze($res_down['dir'] . '/' . $res_down['name']);

if (!empty($getid['tags']['id3v2'])) {
    $tagsArray = $getid['tags']['id3v2'];
} elseif (!empty($getid['tags']['id3v1'])) {
    $tagsArray = $getid['tags']['id3v1'];
}

if (isset($_POST['submit'])) {
    $tagsArray['artist'][0] = isset($_POST['artist']) ? Download::mp3tagsOut($_POST['artist'], 1) : '';
    $tagsArray['title'][0] = isset($_POST['title']) ? Download::mp3tagsOut($_POST['title'], 1) : '';
    $tagsArray['album'][0] = isset($_POST['album']) && !empty($_POST['album']) ? Download::mp3tagsOut($_POST['album'], 1) : '';
    $tagsArray['genre'][0] = isset($_POST['genre']) && !empty($_POST['genre']) ? Download::mp3tagsOut($_POST['genre'], 1) : '';
    $tagsArray['year'][0] = isset($_POST['year']) ? (int)$_POST['year'] : 0;
    require __DIR__ . '../../classes/getid3/write.php';
    $tagsWriter = new getid3_writetags;
    $tagsWriter->filename = $res_down['dir'] . '/' . $res_down['name'];
    $tagsWriter->tagformats = ['id3v1', 'id3v2.3'];
    $tagsWriter->tag_encoding = 'cp1251';
    $tagsWriter->tag_data = $tagsArray;
    $tagsWriter->WriteTags();
    echo '<div class="gmenu">' . _t('Tags saved') . '</div>';
}

echo '<div class="list1"><form action="?act=mp3tags&amp;id=' . $id . '" method="post">' .
    '<b>' . _t('Artist') . '</b>:<br> <input name="artist" type="text" value="' . Download::mp3tagsOut($tagsArray['artist'][0]) . '" /><br>' .
    '<b>' . _t('Title') . '</b>:<br> <input name="title" type="text" value="' . Download::mp3tagsOut($tagsArray['title'][0]) . '" /><br>' .
    '<b>' . _t('Album') . '</b>:<br> <input name="album" type="text" value="' . Download::mp3tagsOut($tagsArray['album'][0]) . '" /><br>' .
    '<b>' . _t('Genre') . '</b>: <br><input name="genre" type="text" value="' . Download::mp3tagsOut($tagsArray['genre'][0]) . '" /><br>' .
    '<b>' . _t('Year') . '</b>:<br> <input name="year" type="text" value="' . (int)$tagsArray['year'][0] . '" /><br>' .
    '<input type="submit" name="submit" value="' . _t('Save') . '"/></form></div>' .
    '<div class="phdr"><a href="?act=view&amp;id=' . $id . '">' . _t('Back') . '</a></div>';

echo $view->render('system::app/legacy', [
    'title'   => _t('Downloads'),
    'content' => ob_get_clean(),
]);
