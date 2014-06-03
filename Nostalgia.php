<?php
/**
 * The ancient Nostalgia skin, plus SkinLegacy support.
 */

$wgExtensionCredits['skin'][] = array(
	'path' => __FILE__,
	'name' => 'Nostalgia',
	'descriptionmsg' => 'nostalgia-desc',
	'url' => 'https://www.mediawiki.org/wiki/Skin:Nostalgia',
);

$wgAutoloadClasses['LegacyTemplate'] = __DIR__ . "/SkinLegacy.php";
$wgAutoloadClasses['SkinLegacy'] = __DIR__ . "/SkinLegacy.php";
$wgAutoloadClasses['SkinNostalgia'] = __DIR__ . "/Nostalgia_body.php";
$wgMessagesDirs['Nostalgia'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['Nostalgia'] = __DIR__ . "/Nostalgia.i18n.php";
$wgValidSkinNames['nostalgia'] = 'Nostalgia';
$wgResourceModules['ext.nostalgia'] = array(
	'styles' => array(
		'screen.css',
		'print.css' => array( 'media' => 'print' ),
	),
	'localBasePath' => __DIR__,
	'remoteBasePath' => $GLOBALS['wgStylePath'] . '/Nostalgia',
);
