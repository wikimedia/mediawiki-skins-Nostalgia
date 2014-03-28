<?php
/**
 * Extension to provide the ancient nostalgia skin, plus SkinLegacy support
 */

if( !defined( 'MEDIAWIKI' ) ) {
	die( -1 );
}

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'Nostalgia',
	'descriptionmsg' => 'nostalgia-desc',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:Nostalgia',
);

// Include stuff!
$dir = __DIR__;
$wgAutoloadClasses['LegacyTemplate'] = "$dir/SkinLegacy.php";
$wgAutoloadClasses['SkinLegacy'] = "$dir/SkinLegacy.php";
$wgAutoloadClasses['SkinNostalgia'] = "$dir/Nostalgia_body.php";
$wgMessagesDirs['Nostalgia'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['Nostalgia'] = "$dir/Nostalgia.i18n.php";
$wgValidSkinNames['nostalgia'] = 'Nostalgia';
$wgResourceModules['ext.nostalgia'] = array(
	'styles' => array(
		'screen.css',
		'print.css' => array( 'media' => 'print' ),
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Nostalgia',
);
