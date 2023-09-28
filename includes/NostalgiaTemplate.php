<?php
/**
 * Nostalgia: A skin which looks like Wikipedia did in its first year (2001).
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\Skin\Nostalgia;

use BaseTemplate;
use Html;
use Linker;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use RawMessage;
use SpecialPage;
use UploadBase;
use XmlSelect;

/**
 * @todo document
 * @ingroup Skins
 */
class NostalgiaTemplate extends BaseTemplate {

	/**
	 * How many search boxes have we made?  Avoid duplicate id's.
	 * @var string|int
	 */
	protected $searchboxes = '';

	/** @var int */
	protected $mWatchLinkNum = 0;

	public function execute() {
		echo $this->beforeContent();
		$this->html( 'bodytext' );
		echo "\n";
		echo $this->afterContent();
		$this->html( 'dataAfterContent' );
	}

	/**
	 * This will be called immediately after the "<body>" tag.
	 * @return string
	 */
	public function beforeContent() {
		$skin = $this->getSkin();
		$s = "\n<div id='content'>\n<div id='top'>\n";
		$s .= '<div id="logo">' . $skin->logoText( 'right' ) . '</div>';

		$s .= $this->pageTitle();
		$s .= "<p class='subtitle'>" . $skin->prepareSubtitle() . "</p>\n";

		$s .= '<div id="topbar">';
		$s .= $this->topLinks() . "\n<br />";

		$notice = $skin->getSiteNotice();
		if ( $notice ) {
			$s .= "\n<div id='siteNotice'>$notice</div>\n";
		}
		$s .= $this->pageTitleLinks();

		$ol = $this->otherLanguages();
		if ( $ol ) {
			$s .= '<br />' . $ol;
		}

		$s .= $skin->getCategories();

		$s .= "<br clear='all' /></div><hr />\n</div>\n";
		$s .= "\n<div id='article'>";

		return $s;
	}

	/**
	 * This gets called shortly before the "</body>" tag.
	 * @return string HTML to be put before "</body>"
	 */
	public function afterContent() {
		$s = "\n</div><br clear='all' />\n";

		$s .= "\n<div id='footer'><hr />";

		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		$s .= $this->bottomLinks();
		$s .= "\n<br />" . $this->pageStats();
		$s .= "\n<br />"
			. $linkRenderer->makeKnownLink(
				Title::newMainPage(),
				$this->getMsg( 'mainpage' )->text()
			)
			. ' | ' . $this->get( 'about', '' )
			. ' | ' . $this->searchForm();

		$s .= "\n</div>\n</div>\n";

		return $s;
	}

	/**
	 * @return string
	 */
	private function searchForm() {
		$skin = $this->getSkin();
		$search = $skin->getRequest()->getText( 'search' );
		$specialAction = SpecialPage::getTitleFor( 'Search' )->getLocalURL();

		$s = '<form id="searchform' . $this->searchboxes
			. '" name="search" class="inline" method="get" action="'
			. htmlspecialchars( $specialAction ) . "\">\n"
			. '<input type="text" id="searchInput' . $this->searchboxes
			. '" name="search" size="19" value="'
			. htmlspecialchars( substr( $search, 0, 256 ) ) . "\" />\n"
			. '<input type="submit" name="go" value="' . $skin->msg( 'searcharticle' )->escaped()
			. '" />'
			. '&#160;<input type="submit" name="fulltext" value="'
			. $skin->msg( 'searchbutton' )->escaped() . "\" />\n"
			. '</form>';

		// Ensure unique id's for search boxes made after the first
		$this->searchboxes = $this->searchboxes == '' ? 2 : $this->searchboxes + 1;

		return $s;
	}

	/**
	 * @return string
	 */
	private function pageStats() {
		$ret = [];
		$items = [ 'viewcount', 'credits', 'lastmod', 'numberofwatchingusers', 'copyright' ];

		foreach ( $items as $item ) {
			if ( $this->data[$item] !== false ) {
				$ret[] = $this->data[$item];
			}
		}

		return implode( ' ', $ret );
	}

	/**
	 * @return string
	 */
	private function topLinks() {
		$sep = " |\n";
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

		$skin = $this->getSkin();
		$s = $linkRenderer->makeKnownLink(
				Title::newMainPage(),
				$this->getMsg( 'mainpage' )->text()
			) . $sep
			. Linker::specialLink( 'Recentchanges' );

		if ( $this->data['isarticle'] ) {
			$s .= $sep . '<strong>' . $this->editThisPage() . '</strong>' . $sep . $this->talkLink()
				. $sep . $this->historyLink();
		}

		/* show links to different language variants */
		$s .= $this->variantLinks();
		if ( !$this->data['loggedin'] ) {
			$s .= $sep . Linker::specialLink( 'Userlogin' );
		} else {
			/* show user page and user talk links */
			$user = $skin->getUser();
			$s .= $sep . Linker::link( $user->getUserPage(), $skin->msg( 'mypage' )->escaped() );
			$s .= $sep . Linker::link( $user->getTalkPage(), $skin->msg( 'mytalk' )->escaped() );

			$userHasNewMessages = MediaWikiServices::getInstance()
				->getTalkPageNotificationManager()->userHasNewMessages( $user );
			if ( $userHasNewMessages ) {
				$s .= ' *';
			}
			/* show watchlist link */
			$s .= $sep . Linker::specialLink( 'Watchlist' );
			/* show my contributions link */
			$s .= $sep . Linker::link(
				SpecialPage::getSafeTitleFor( 'Contributions', $this->data['username'] ),
				$skin->msg( 'mycontris' )->escaped() );
			/* show my preferences link */
			$s .= $sep . Linker::specialLink( 'Preferences' );
			/* show upload file link */
			if ( UploadBase::isEnabled() && UploadBase::isAllowed( $user ) === true ) {
				$s .= $sep . $this->getUploadLink();
			}

			/* show log out link */
			$s .= $sep . Linker::specialLink( 'Userlogout' );
		}

		$s .= $sep . $this->specialPagesList();

		return $s;
	}

	/**
	 * Language/charset variant links for classic-style skins
	 * @return string
	 */
	private function variantLinks() {
		$s = '';

		/* show links to different language variants */
		global $wgDisableLangConversion;

		$skin = $this->getSkin();
		$title = $skin->getTitle();
		$lang = $title->getPageLanguage();
		$variants = MediaWikiServices::getInstance()->getLanguageConverterFactory()
			->getLanguageConverter( $lang )
			->getVariants();
		$userLang = $skin->getLanguage();

		if ( !$wgDisableLangConversion && count( $variants ) > 1
			&& !$title->isSpecialPage() ) {
			foreach ( $variants as $code ) {
				$varname = $lang->getVariantname( $code );

				if ( $varname == 'disable' ) {
					continue;
				}
				$s = $userLang->pipeList( [
					$s,
					'<a href="' . htmlspecialchars( $title->getLocalURL( 'variant=' . $code ) )
						. '" lang="' . $code . '" hreflang="' . $code . '">'
						. htmlspecialchars( $varname ) . '</a>',
				] );
			}
		}

		return $s;
	}

	/**
	 * @return string
	 */
	private function bottomLinks() {
		$skin = $this->getSkin();
		$sep = $skin->msg( 'pipe-separator' )->escaped() . "\n";
		$out = $skin->getOutput();
		$user = $skin->getUser();

		$s = '';
		if ( $out->isArticleRelated() ) {
			$element = [ '<strong>' . $this->editThisPage() . '</strong>' ];

			if ( $user->isRegistered() ) {
				$element[] = $this->watchThisPage();
			}

			$element[] = $this->talkLink();
			$element[] = $this->historyLink();
			$element[] = $this->whatLinksHere();
			$element[] = $this->watchPageLinksLink();

			$title = $skin->getTitle();

			if (
				$title->getNamespace() == NS_USER ||
				$title->getNamespace() == NS_USER_TALK
			) {
				$services = MediaWikiServices::getInstance();
				$userIdentity = $services->getUserIdentityLookup()->getUserIdentityByName( $title->getText() );
				$userNameUtils = $services->getUserNameUtils();
				$ip = $userNameUtils->isIP( $title->getText() );

				# Both anons and non-anons have contributions list
				if ( ( $userIdentity && $userIdentity->isRegistered() ) || $ip ) {
					$element[] = $this->userContribsLink();
				}

				if ( $userIdentity && $userIdentity->isRegistered() && $skin->showEmailUser( $userIdentity ) ) {
					$element[] = $this->emailUserLink();
				}
			}

			$s = implode( $sep, $element );

			if ( $title->getArticleID() ) {
				$s .= "\n<br />";

				// Delete/protect/move links for privileged users
				if ( $user->isAllowed( 'delete' ) ) {
					$s .= $this->deleteThisPage();
				}

				if ( $user->isAllowed( 'protect' ) &&
					MediaWikiServices::getInstance()->getRestrictionStore()->listApplicableRestrictionTypes( $title )
				) {
					$s .= $sep . $this->protectThisPage();
				}

				if ( $user->isAllowed( 'move' ) ) {
					$s .= $sep . $this->moveThisPage();
				}
			}

			$s .= "<br />\n" . $this->otherLanguages();
		}

		return $s;
	}

	/**
	 * @return string
	 */
	private function otherLanguages() {
		global $wgHideInterlanguageLinks;

		if ( $wgHideInterlanguageLinks ) {
			return '';
		}

		$skin = $this->getSkin();
		$a = $skin->getOutput()->getLanguageLinks();

		if ( count( $a ) === 0 ) {
			return '';
		}

		$s = $skin->msg( 'otherlanguages' )->escaped() . $skin->msg( 'colon-separator' )->escaped();
		$first = true;
		$lang = $skin->getLanguage();

		if ( $lang->isRTL() ) {
			$s .= '<span dir="ltr">';
		}

		$languageNameUtils = MediaWikiServices::getInstance()->getLanguageNameUtils();
		foreach ( $a as $l ) {
			if ( !$first ) {
				$s .= $skin->msg( 'pipe-separator' )->escaped();
			}

			$first = false;

			$nt = Title::newFromText( $l );
			$text = $languageNameUtils->getLanguageName( $nt->getInterwiki() );

			$s .= Html::element( 'a',
				[ 'href' => $nt->getFullURL(), 'title' => $nt->getText(), 'class' => 'external' ],
				$text == '' ? $l : $text );
		}

		if ( $lang->isRTL() ) {
			$s .= '</span>';
		}

		return $s;
	}

	/**
	 * Show a drop-down box of special pages
	 * @return string
	 */
	private function specialPagesList() {
		global $wgScript;

		$skin = $this->getSkin();
		$select = new XmlSelect( 'title' );
		$factory = MediaWikiServices::getInstance()->getSpecialPageFactory();
		$pages = $factory->getUsablePages( $skin->getUser() );
		array_unshift( $pages, $factory->getPage( 'SpecialPages' ) );
		/** @var SpecialPage[] $pages */
		foreach ( $pages as $obj ) {
			$desc = $obj->getDescription();
			if ( is_string( $desc ) ) {
				// T343849: returning a string from ::getDescription() is deprecated.
				$desc = ( new RawMessage( '$1' ) )->rawParams( $desc );
			}
			$select->addOption( $desc->text(),
				$obj->getPageTitle()->getPrefixedDBkey() );
		}

		return Html::rawElement( 'form',
			[ 'id' => 'specialpages', 'method' => 'get', 'action' => $wgScript ],
			$select->getHTML() . Html::element(
				'input',
				[ 'type' => 'submit', 'value' => $skin->msg( 'go' )->text() ]
			)
		);
	}

	/**
	 * @return string
	 */
	private function pageTitleLinks() {
		$skin = $this->getSkin();
		$title = $skin->getTitle();
		$out = $skin->getOutput();
		$user = $skin->getUser();
		$lang = $skin->getLanguage();
		$request = $skin->getRequest();

		$oldid = $request->getVal( 'oldid' );
		$diff = $request->getVal( 'diff' );
		$action = $request->getText( 'action' );

		$s = [ $this->printableLink() ];
		$disclaimer = $this->get( 'disclaimers', '' );

		# may be empty
		if ( $disclaimer ) {
			$s[] = $disclaimer;
		}

		$privacy = $this->get( 'privacy' );

		# may be empty too
		if ( $privacy ) {
			$s[] = $privacy;
		}

		if ( $out->isArticleRelated() && $title->getNamespace() == NS_FILE ) {
			$image = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $title );

			if ( $image ) {
				$href = $image->getUrl();
				$s[] = Html::element( 'a', [ 'href' => $href,
					'title' => $href ], $title->getText() );

			}
		}

		if ( $action == 'history' || isset( $diff ) || isset( $oldid ) ) {
			$s[] .= Linker::linkKnown(
				$title,
				$skin->msg( 'currentrev' )->escaped()
			);
		}

		$userHasNewMessages = MediaWikiServices::getInstance()
			->getTalkPageNotificationManager()->userHasNewMessages( $user );
		# do not show "You have new messages" text when we are viewing our
		# own talk page
		if ( $userHasNewMessages && !$title->equals( $user->getTalkPage() ) ) {
			$tl = Linker::linkKnown(
				$user->getTalkPage(),
				$skin->msg( 'nostalgia-newmessageslink' )->escaped(),
				[],
				[ 'redirect' => 'no' ]
			);

			$dl = Linker::linkKnown(
				$user->getTalkPage(),
				$skin->msg( 'nostalgia-newmessagesdifflink' )->escaped(),
				[],
				[ 'diff' => 'cur' ]
			);
			$s[] = '<strong>' . $skin->msg( 'youhavenewmessages' )
				->rawParams( $tl, $dl )->escaped() . '</strong>';
			# disable caching
			$out->setCdnMaxage( 0 );
			$out->disableClientCache();
		}

		$undelete = $skin->getUndeleteLink();

		if ( !empty( $undelete ) ) {
			$s[] = $undelete;
		}

		return $lang->pipeList( $s );
	}

	/**
	 * Gets the h1 element with the page title.
	 * @return string
	 */
	private function pageTitle() {
		return '<h1 class="pagetitle">' .
			$this->getSkin()->getOutput()->getPageTitle() .
			'</h1>';
	}

	/**
	 * @return string
	 */
	private function printableLink() {
		$skin = $this->getSkin();
		$out = $skin->getOutput();
		$lang = $skin->getLanguage();
		$request = $skin->getRequest();

		$s = [];

		if ( !$out->isPrintable() ) {
			$printurl = htmlspecialchars( $skin->getTitle()->getLocalURL(
				$request->appendQueryValue( 'printable', 'yes' ) ) );
			$s[] = "<a href=\"$printurl\" rel=\"alternate\">"
				. $skin->msg( 'printableversion' )->escaped() . '</a>';
		}

		if ( $out->isSyndicated() ) {
			foreach ( $out->getSyndicationLinks() as $format => $link ) {
				$feedUrl = htmlspecialchars( $link );
				$s[] = "<a href=\"$feedUrl\" rel=\"alternate\" type=\"application/{$format}+xml\""
						. " class=\"feedlink\">" . $skin->msg( "feed-$format" )->escaped() . "</a>";
			}
		}
		return $lang->pipeList( $s );
	}

	/**
	 * @return string
	 */
	private function editThisPage() {
		$skin = $this->getSkin();
		if ( !$skin->getOutput()->isArticleRelated() ) {
			$s = $skin->msg( 'protectedpage' )->escaped();
		} else {
			$title = $skin->getTitle();
			$user = $skin->getUser();
			$permManager = MediaWikiServices::getInstance()->getPermissionManager();
			if ( $permManager->quickUserCan( 'edit', $user, $title ) && $title->exists() ) {
				$t = $skin->msg( 'nostalgia-editthispage' )->escaped();
			} elseif ( $permManager->quickUserCan( 'create', $user, $title ) && !$title->exists() ) {
				$t = $skin->msg( 'nostalgia-create-this-page' )->escaped();
			} else {
				$t = $skin->msg( 'viewsource' )->escaped();
			}

			$s = Linker::linkKnown(
				$title,
				$t,
				[],
				$skin->editUrlOptions()
			);
		}

		return $s;
	}

	/**
	 * @return string
	 */
	private function deleteThisPage() {
		$skin = $this->getSkin();
		$diff = $skin->getRequest()->getVal( 'diff' );
		$title = $skin->getTitle();

		if ( $title->getArticleID() && ( !$diff ) &&
			$skin->getUser()->isAllowed( 'delete' ) ) {
			$t = $skin->msg( 'nostalgia-deletethispage' )->escaped();

			$s = Linker::linkKnown(
				$title,
				$t,
				[],
				[ 'action' => 'delete' ]
			);
		} else {
			$s = '';
		}

		return $s;
	}

	/**
	 * @return string
	 */
	private function protectThisPage() {
		$skin = $this->getSkin();
		$diff = $skin->getRequest()->getVal( 'diff' );
		$title = $skin->getTitle();
		$restrictionStore = MediaWikiServices::getInstance()->getRestrictionStore();

		if ( $title->getArticleID() && ( !$diff ) &&
			$skin->getUser()->isAllowed( 'protect' ) &&
			$restrictionStore->listApplicableRestrictionTypes( $title )
		) {
			if ( $restrictionStore->isProtected( $title ) ) {
				$text = $skin->msg( 'nostalgia-unprotectthispage' )->escaped();
				$query = [ 'action' => 'unprotect' ];
			} else {
				$text = $skin->msg( 'nostalgia-protectthispage' )->escaped();
				$query = [ 'action' => 'protect' ];
			}

			$s = Linker::linkKnown(
				$title,
				$text,
				[],
				$query
			);
		} else {
			$s = '';
		}

		return $s;
	}

	/**
	 * @return string
	 */
	private function watchThisPage() {
		++$this->mWatchLinkNum;

		// Cache
		$skin = $this->getSkin();
		$title = $skin->getTitle();

		if ( $skin->getOutput()->isArticleRelated() ) {
			if ( MediaWikiServices::getInstance()->getWatchlistManager()->isWatched( $skin->getUser(), $title ) ) {
				$text = $skin->msg( 'unwatchthispage' )->escaped();
				$query = [
					'action' => 'unwatch',
				];
				$id = 'mw-unwatch-link' . $this->mWatchLinkNum;
			} else {
				$text = $skin->msg( 'watchthispage' )->escaped();
				$query = [
					'action' => 'watch',
				];
				$id = 'mw-watch-link' . $this->mWatchLinkNum;
			}

			$s = Linker::linkKnown(
				$title,
				$text,
				[
					'id' => $id,
					'class' => 'mw-watchlink',
				],
				$query
			);
		} else {
			$s = $skin->msg( 'notanarticle' )->escaped();
		}

		return $s;
	}

	/**
	 * @return string
	 */
	private function moveThisPage() {
		$skin = $this->getSkin();
		$title = $skin->getTitle();
		$permManager = MediaWikiServices::getInstance()->getPermissionManager();
		$user = $skin->getUser();

		if ( $permManager->quickUserCan( 'move', $user, $title ) ) {
			return Linker::linkKnown(
				SpecialPage::getTitleFor( 'Movepage' ),
				$skin->msg( 'movethispage' )->escaped(),
				[],
				[ 'target' => $title->getPrefixedDBkey() ]
			);
		}

		// no message if page is protected - would be redundant
		return '';
	}

	/**
	 * @return string
	 */
	private function historyLink() {
		$skin = $this->getSkin();
		return Linker::link(
			$skin->getTitle(),
			$skin->msg( 'history' )->escaped(),
			[ 'rel' => 'archives' ],
			[ 'action' => 'history' ]
		);
	}

	/**
	 * @return string
	 */
	private function whatLinksHere() {
		$skin = $this->getSkin();
		return Linker::linkKnown(
			SpecialPage::getTitleFor( 'Whatlinkshere', $skin->getTitle()->getPrefixedDBkey() ),
			$skin->msg( 'whatlinkshere' )->escaped()
		);
	}

	/**
	 * @return string
	 */
	private function userContribsLink() {
		$skin = $this->getSkin();
		return Linker::linkKnown(
			SpecialPage::getTitleFor( 'Contributions', $skin->getTitle()->getDBkey() ),
			$skin->msg( 'contributions' )->escaped()
		);
	}

	/**
	 * @return string
	 */
	private function emailUserLink() {
		$skin = $this->getSkin();
		return Linker::linkKnown(
			SpecialPage::getTitleFor( 'Emailuser', $skin->getTitle()->getDBkey() ),
			$skin->msg( 'emailuser' )->escaped()
		);
	}

	/**
	 * @return string
	 */
	private function watchPageLinksLink() {
		$skin = $this->getSkin();
		if ( !$skin->getOutput()->isArticleRelated() ) {
			return $skin->msg( 'parentheses', $skin->msg( 'notanarticle' )->text() )->escaped();
		}

		return Linker::linkKnown(
			SpecialPage::getTitleFor( 'Recentchangeslinked',
				$skin->getTitle()->getPrefixedDBkey() ),
			$skin->msg( 'recentchangeslinked-toolbox' )->escaped()
		);
	}

	/**
	 * @return string
	 */
	private function talkLink() {
		$skin = $this->getSkin();
		$title = $skin->getTitle();
		if ( $title->isSpecialPage() ) {
			# No discussion links for special pages
			return '';
		}

		$linkOptions = [];

		if ( $title->isTalkPage() ) {
			$link = $title->getSubjectPage();
			switch ( $link->getNamespace() ) {
				case NS_MAIN:
					$text = $skin->msg( 'nostalgia-articlepage' );
					break;
				case NS_USER:
					$text = $skin->msg( 'nostalgia-userpage' );
					break;
				case NS_PROJECT:
					$text = $skin->msg( 'nostalgia-projectpage' );
					break;
				case NS_FILE:
					$text = $skin->msg( 'imagepage' );
					# Make link known if image exists, even if the desc. page doesn't.
					if ( MediaWikiServices::getInstance()->getRepoGroup()->findFile( $link ) ) {
						$linkOptions[] = 'known';
					}
					break;
				case NS_MEDIAWIKI:
					$text = $skin->msg( 'mediawikipage' );
					break;
				case NS_TEMPLATE:
					$text = $skin->msg( 'templatepage' );
					break;
				case NS_HELP:
					$text = $skin->msg( 'viewhelppage' );
					break;
				case NS_CATEGORY:
					$text = $skin->msg( 'categorypage' );
					break;
				default:
					$text = $skin->msg( 'nostalgia-articlepage' );
			}
		} else {
			$link = $title->getTalkPage();
			$text = $skin->msg( 'nostalgia-talkpage' );
		}

		return Linker::link( $link, $text->escaped(), [], [], $linkOptions );
	}

	/**
	 * @return string
	 */
	private function getUploadLink() {
		global $wgUploadNavigationUrl;

		if ( $wgUploadNavigationUrl ) {
			# Using an empty class attribute to avoid automatic setting of "external" class
			return Linker::makeExternalLink( $wgUploadNavigationUrl,
				$this->getSkin()->msg( 'upload' )->text(),
				true, '', [ 'class' => '' ] );
		}

		return Linker::linkKnown(
			SpecialPage::getTitleFor( 'Upload' ),
			$this->getSkin()->msg( 'upload' )->escaped()
		);
	}
}
