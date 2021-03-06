<?php

 /* · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · ·  
  ·                                                                             ·
  ·                                                                             ·
  ·                             Q V I T T E R                                   ·
  ·                                                                             ·
  ·              http://github.com/hannesmannerheim/qvitter                     ·
  ·                                                                             ·
  ·                                                                             ·
  ·                                 <o)                                         ·
  ·                                  /_////                                     ·
  ·                                 (____/                                      ·
  ·                                          (o<                                ·
  ·                                   o> \\\\_\                                 ·
  ·                                 \\)   \____)                                ·
  ·                                                                             ·
  ·                                                                             ·    
  ·                                                                             ·
  ·  Qvitter is free  software:  you can  redistribute it  and / or  modify it  ·
  ·  under the  terms of the GNU Affero General Public License as published by  ·
  ·  the Free Software Foundation,  either version three of the License or (at  ·
  ·  your option) any later version.                                            ·
  ·                                                                             ·
  ·  Qvitter is distributed  in hope that  it will be  useful but  WITHOUT ANY  ·
  ·  WARRANTY;  without even the implied warranty of MERCHANTABILTY or FITNESS  ·
  ·  FOR A PARTICULAR PURPOSE.  See the  GNU Affero General Public License for  ·
  ·  more details.                                                              ·
  ·                                                                             ·
  ·  You should have received a copy of the  GNU Affero General Public License  ·
  ·  along with Qvitter. If not, see <http://www.gnu.org/licenses/>.            ·
  ·                                                                             ·
  ·  Contact h@nnesmannerhe.im if you have any questions.                       ·
  ·                                                                             · 
  · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · · */
class QvitterAction extends ApiAction
{

    function isReadOnly($args)
    {
        return true;
    }

    protected function prepare(array $args=array())
    {
        parent::prepare($args);

        $user = common_current_user();

        return true;
    }

    protected function handle()
    {
        parent::handle();
        
        $this->showQvitter();

    }

    function showQvitter()
    {


		$logged_in_user_nickname = '';
		$logged_in_user_obj = false;		
		$logged_in_user = common_current_user();
		if($logged_in_user) {
			$logged_in_user_nickname = $logged_in_user->nickname;
			$logged_in_user_obj = ApiAction::twitterUserArray($logged_in_user->getProfile());
			}
			
		$registrationsclosed = false;
		if(common_config('site','closed') == 1 || common_config('site','inviteonly') == 1) {
			$registrationsclosed = true;
			}

		// check if the client's ip address is blocked for registration
		if(is_array(QvitterPlugin::settings("blocked_ips"))) {
			$client_ip_is_blocked = in_array($_SERVER['REMOTE_ADDR'], QvitterPlugin::settings("blocked_ips"));			
			}	
			
		$sitetitle = common_config('site','name');
		$siterootdomain = common_config('site','server');
		$qvitterpath = Plugin::staticPath('Qvitter', '');
		$apiroot = common_path('api/', StatusNet::isHTTPS());
		$attachmentroot = common_path('attachment/', StatusNet::isHTTPS());
		$instanceurl = common_path('', StatusNet::isHTTPS());
		
		

		common_set_returnto(''); // forget this

		// if this is a profile we add a link header for LRDD Discovery (see WebfingerPlugin.php)
		if(substr_count($_SERVER['REQUEST_URI'], '/') == 1) { 
			$nickname = substr($_SERVER['REQUEST_URI'],1);
			if(preg_match("/^[a-zA-Z0-9]+$/", $nickname) == 1) {
					$acct = 'acct:'. $nickname .'@'. common_config('site', 'server');
					$url = common_local_url('webfinger') . '?resource='.$acct;
					foreach (array(Discovery::JRD_MIMETYPE, Discovery::XRD_MIMETYPE) as $type) {
						header('Link: <'.$url.'>; rel="'. Discovery::LRDD_REL.'"; type="'.$type.'"');
					}
				}
			}	

		?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
		"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
			<head>  
				<title><?php print $sitetitle; ?></title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">		
				<link rel="stylesheet" type="text/css" href="<?php print $qvitterpath; ?>css/qvitter.css?changed=<?php print date('YmdHis',filemtime(QVITTERDIR.'/css/qvitter.css')); ?>" />
				<link rel="stylesheet" type="text/css" href="<?php print $qvitterpath; ?>css/jquery.minicolors.css" />		
				<link rel="shortcut icon" type="image/x-icon" href="<?php print $qvitterpath; ?><?php print QvitterPlugin::settings("favicon"); ?>">
				
				<!-- GOTABULO -->
                <?php if(defined('GOTABULO')) { ?>
                       <link rel="stylesheet" type="text/css" href="<?php print $qvitterpath; ?>css/wgo.player.css" />
                <?php } ?>

				<?php

				// if qvitter is a webapp and this is a users url we add feeds
				if(substr_count($_SERVER['REQUEST_URI'], '/') == 1) { 
					$nickname = substr($_SERVER['REQUEST_URI'],1);
					if(preg_match("/^[a-zA-Z0-9]+$/", $nickname) == 1) {
						$user = User::getKV('nickname', $nickname); 
						if(!isset($user->id)) {
							//error_log("QVITTER: Could not get user id for user with nickname: $nickname – REQUEST_URI: ".$_SERVER['REQUEST_URI']);
							}        
						else {
							print '<link title="Notice feed for '.$nickname.' (Activity Streams JSON)" type="application/stream+json" href="'.$instanceurl.'api/statuses/user_timeline/'.$user->id.'.as" rel="alternate">'."\n";
							print '				<link title="Notice feed for '.$nickname.' (RSS 1.0)" type="application/rdf+xml" href="'.$instanceurl.$nickname.'/rss" rel="alternate">'."\n";
							print '				<link title="Notice feed for '.$nickname.' (RSS 2.0)" type="application/rss+xml" href="'.$instanceurl.'api/statuses/user_timeline/'.$user->id.'.rss" rel="alternate">'."\n";
							print '				<link title="Notice feed for '.$nickname.' (Atom)" type="application/atom+xml" href="'.$instanceurl.'api/statuses/user_timeline/'.$user->id.'.atom" rel="alternate">'."\n";
							print '				<link title="FOAF for '.$nickname.'" type="application/rdf+xml" href="'.$instanceurl.$nickname.'/foaf" rel="meta">'."\n";
							print '				<link href="'.$instanceurl.$nickname.'/microsummary" rel="microsummary">'."\n";		    
							
							// maybe openid
							if (array_key_exists('OpenID', StatusNet::getActivePlugins())) {
								print '				<link rel="openid2.provider" href="'.common_local_url('openidserver').'"/>'."\n";
								print '				<link rel="openid2.local_id" href="'.$user->getProfile()->profileurl.'"/>'."\n";
								print '				<link rel="openid2.server" href="'.common_local_url('openidserver').'"/>'."\n";
								print '				<link rel="openid2.delegate" href="'.$user->getProfile()->profileurl.'"/>'."\n";																											
								}														
							}		
						}
					}
				elseif(substr($_SERVER['REQUEST_URI'],0,7) == '/group/') {
					$group_id_or_name = substr($_SERVER['REQUEST_URI'],7);
					if(stristr($group_id_or_name,'/id')) {
						$group_id_or_name = substr($group_id_or_name, 0, strpos($group_id_or_name,'/id'));
						$group = User_group::getKV('id', $group_id_or_name);		
						$group_name = $group->nickname;
						$group_id = $group_id_or_name;				
						}
					else {
						$group = Local_group::getKV('nickname', $group_id_or_name);
						$group_id = $group->group_id;
						$group_name = $group_id_or_name;								
						}
					if(preg_match("/^[a-zA-Z0-9]+$/", $group_id_or_name) == 1) {
                ?>

				<link rel="alternate" href="<?php echo htmlspecialchars(common_local_url('ApiTimelineGroup', array('id'=>$group_id, 'format'=>'as'))); ?>" type="application/stream+json" title="Notice feed for '<?php echo htmlspecialchars($group_name); ?>' group (Activity Streams JSON)" />
				<link rel="alternate" href="<?php echo htmlspecialchars(common_local_url('grouprss', array('nickname'=>$group_name))); ?>" type="application/rdf+xml" title="Notice feed for '<?php echo htmlspecialchars($group_name); ?>' group (RSS 1.0)" />
				<link rel="alternate" href="<?php echo htmlspecialchars(common_local_url('ApiTimelineGroup', array('id'=>$group_id, 'format'=>'rss'))); ?>" type="application/rss+xml" title="Notice feed for '<?php echo htmlspecialchars($group_name); ?>' group (RSS 2.0)" />
				<link rel="alternate" href="<?php echo htmlspecialchars(common_local_url('ApiTimelineGroup', array('id'=>$group_id, 'format'=>'atom'))); ?>" type="application/atom+xml" title="Notice feed for '<?php echo htmlspecialchars($group_name); ?>' group (Atom)" />
				<link rel="meta" href="<?php echo htmlspecialchars(common_local_url('foafgroup', array('nickname'=>$group_name))); ?>" type="application/rdf+xml" title="FOAF for '<?php echo htmlspecialchars($group_name); ?>' group" />
                <?php
						}
					}			
				
				
				
				?><script>
					window.defaultAvatarStreamSize = <?php print json_encode(Avatar::defaultImage(AVATAR_STREAM_SIZE)) ?>;
					window.textLimit = <?php print json_encode((int)common_config('site','textlimit')) ?>;
					window.registrationsClosed = <?php print json_encode($registrationsclosed) ?>;
					window.thisSiteThinksItIsHttpButIsActuallyHttps = <?php
					
						if(isset($_SERVER['HTTPS'])
						&& $_SERVER['HTTPS'] != 'off'
						&& substr($instanceurl,0,7) == 'http://') {
							print 'true';
							}
						else {
							print 'false';
							}
					
					?>;
					window.siteTitle = <?php print json_encode($sitetitle) ?>;
					window.loggedIn = <?php 
					
						$logged_in_user_json = json_encode($logged_in_user_obj);
						$logged_in_user_json = str_replace('http:\/\/quitter.se\/','https:\/\/quitter.se\/',$logged_in_user_json);    	
						print $logged_in_user_json; 
					
					?>;
					window.timeBetweenPolling = <?php print QvitterPlugin::settings("timebetweenpolling"); ?>;
					window.apiRoot = '<?php print common_path("api/", StatusNet::isHTTPS()); ?>';
					window.fullUrlToThisQvitterApp = '<?php print $qvitterpath; ?>';
					window.siteRootDomain = '<?php print $siterootdomain; ?>';
					window.siteInstanceURL = '<?php print $instanceurl; ?>';			
					window.defaultLinkColor = '<?php print QvitterPlugin::settings("defaultlinkcolor"); ?>';
					window.defaultBackgroundColor = '<?php print QvitterPlugin::settings("defaultbackgroundcolor"); ?>';
					window.siteBackground = '<?php print QvitterPlugin::settings("sitebackground"); ?>';
					window.enableWelcomeText = '<?php print QvitterPlugin::settings("enablewelcometext"); ?>';
					window.urlShortenerAPIURL = '<?php print QvitterPlugin::settings("urlshortenerapiurl"); ?>';					
					window.urlShortenerSignature = '<?php print QvitterPlugin::settings("urlshortenersignature"); ?>';
					window.commonSessionToken = '<?php print common_session_token(); ?>';
					window.siteMaxThumbnailSize = <?php print common_config('thumbnail', 'maxsize'); ?>;
					window.siteAttachmentURLBase = '<?php print $attachmentroot; ?>';					
					window.siteEmail = '<?php print common_config('site', 'email'); ?>';										
					window.siteLicenseTitle = '<?php print common_config('license', 'title'); ?>';
					window.siteLicenseURL = '<?php print common_config('license', 'url'); ?>';
					window.customTermsOfUse = <?php print json_encode(QvitterPlugin::settings("customtermsofuse")); ?>;	
					
					// available language files and their last update time
					window.availableLanguages = {<?php
					
					// scan all files in the locale directory and create a json object with their change date added
					$available_languages = array_diff(scandir(QVITTERDIR.'/locale'), array('..', '.'));
					foreach($available_languages as $lan) {
						$lancode = substr($lan,0,strpos($lan,'.'));
						print "\n".'						"'.$lancode.'": "'.$lan.'?changed='.date('YmdHis',filemtime(QVITTERDIR.'/locale/'.$lan)).'",';
						
						// also make an array with all language names, to use for generating menu
						$languagecodesandnames[$lancode]['english_name'] = Locale::getDisplayLanguage($lancode, 'en');
						$languagecodesandnames[$lancode]['name'] = Locale::getDisplayLanguage($lancode, $lancode);
						if(Locale::getDisplayRegion($lancode, $lancode)) {
							$languagecodesandnames[$lancode]['name'] .= ' ('.Locale::getDisplayRegion($lancode, $lancode).')';							
							}
						if($lancode == 'es_ahorita') { $languagecodesandnames[$lancode]['name'] = 'español (ahorita)'; } // joke
						}				
						?>
					
						};
					
				</script>
				<style>
					a, a:visited, a:active,
					ul.stats li:hover a,
					ul.stats li:hover a strong,
					#user-body a:hover div strong,
					#user-body a:hover div div,
					.permalink-link:hover,
					.stream-item.expanded > .queet .stream-item-expand,
					.stream-item-footer .with-icn .requeet-text a b:hover,
					.queet-text span.attachment.more,
					.stream-item-header .created-at a:hover,
					.stream-item-header a.account-group:hover .name,
					.queet:hover .stream-item-expand,
					.show-full-conversation:hover,
					#new-queets-bar,
					.menu-container div,	
					.cm-mention, .cm-tag, .cm-group, .cm-url, .cm-email,
					div.syntax-middle span,
					#user-body strong,
					ul.stats,
					.stream-item:not(.temp-post) ul.queet-actions li .icon:not(.is-mine):hover:before,
					.show-full-conversation,
					#user-body #user-queets:hover .label,
					#user-body #user-groups:hover .label, 
					#user-body #user-following:hover .label,
					ul.stats a strong,
					.queet-box-extras button,
					#openid-login:hover:after {
						color:<?php print QvitterPlugin::settings("defaultlinkcolor"); ?>;/*COLOREND*/
						}			
					#unseen-notifications,
					.stream-item.notification .not-seen,
					#top-compose,
					#logo,
					.queet-toolbar button,
					#user-header,
					.profile-header-inner,
					.topbar,
					.menu-container,
					.member-button.member,
					.external-follow-button.following,
					.qvitter-follow-button.following,
					.save-profile-button,
					.crop-and-save-button,
					.topbar .global-nav.show-logo:before,
					.topbar .global-nav.pulse-logo:before {
						background-color:<?php print QvitterPlugin::settings("defaultlinkcolor"); ?>;/*BACKGROUNDCOLOREND*/
						}	
					.queet-box-syntax[contenteditable="true"]:focus {
						border-color:#999999;/*BORDERCOLOREND*/						
						}
					#user-footer-inner,
					.inline-reply-queetbox,
					#popup-faq #faq-container p.indent {
						background-color:rgb(205,230,239);/*LIGHTERBACKGROUNDCOLOREND*/
						}
					#user-footer-inner,
					.queet-box,
					.queet-box-syntax[contenteditable="true"],
					.inline-reply-queetbox,
					span.inline-reply-caret,
				    .stream-item.expanded .stream-item.first-visible-after-parent,
					#popup-faq #faq-container p.indent {
						border-color:rgb(155,206,224);/*LIGHTERBORDERCOLOREND*/
						}
					span.inline-reply-caret .caret-inner {
						border-bottom-color:rgb(205,230,239);/*LIGHTERBORDERBOTTOMCOLOREND*/
						}
						
				</style>
				<?php
				
	            // event for other plugins to use to add head elements to qvitter
	            Event::handle('QvitterEndShowHeadElements', array($this));			
				
				?>
			</head>
			<body style="background-color:<?php print QvitterPlugin::settings("defaultbackgroundcolor"); ?>">
				<input id="upload-image-input" class="upload-image-input" type="file" name="upload-image-input" accept="image/*"> 
				
			    <?php if(defined('GOTABULO')) { ?>
                        <input id="upload-sgf-input" class="upload-sgf-input" type="file" name="upload-sgf-input" accept=".sgf">				
				<?php } ?>
				<div class="topbar">
					<a href="<?php print $instanceurl; ?>"><div id="logo"></div></a>
					<a id="settingslink">
						<div class="dropdown-toggle">
							<div class="nav-session"></div>
						</div>
					</a>
					<div id="top-compose" class="hidden"></div>
					<ul class="quitter-settings dropdown-menu">
						<li class="dropdown-caret right">
							<span class="caret-outer"></span>
							<span class="caret-inner"></span>
						</li>
						<li class="fullwidth"><a id="logout"></a></li>
						<li class="fullwidth dropdown-divider"></li>
						<li class="fullwidth"><a id="edit-profile-header-link"></a></li>						
						<li class="fullwidth"><a id="settings" href="<?php print $instanceurl; ?>settings/profile" donthijack></a></li>						
						<li class="fullwidth"><a id="faq-link"></a></li>	
						<?php if (common_config('invite', 'enabled') && !common_config('site', 'closed')) { ?>
							<li class="fullwidth"><a id="invite-link" href="<?php print $instanceurl; ?>main/invite"></a></li>
						<?php } ?>	
						<li class="fullwidth"><a id="classic-link"></a></li>												
						<li class="fullwidth language dropdown-divider"></li>
						<?php
						
						// languages
						foreach($languagecodesandnames as $lancode=>$lan) {
							print '<li class="language"><a class="language-link" title="'.$lan['english_name'].'" data-lang-code="'.$lancode.'">'.$lan['name'].'</a></li>';
							}
						
						?>													
					</ul>	
					<div class="global-nav">
						<div class="global-nav-inner">
							<div class="container">				
								<div id="search">
									<input type="text" spellcheck="false" autocomplete="off" name="q" placeholder="Sök" id="search-query" class="search-input">
									<span class="search-icon">
										<button class="icon nav-search" type="submit" tabindex="-1">
											<span> Sök </span>
										</button>						    
									</span>
								</div>	
								<ul class="language-dropdown">
									<li class="dropdown">
										<a class="dropdown-toggle">
											<small></small>
											<span class="current-language"></span>
											<b class="caret"></b>
										</a>
										<ul class="dropdown-menu">
											<li class="dropdown-caret right">
												<span class="caret-outer"></span>
												<span class="caret-inner"></span>
											</li>
											<?php
						
											// languages
											foreach($languagecodesandnames as $lancode=>$lan) {
												print '<li><a class="language-link" title="'.$lan['english_name'].'" data-lang-code="'.$lancode.'">'.$lan['name'].'</a></li>';
												}
						
											?>							
										</ul>
									</li>
								</ul>					
							</div>
						</div>
					</div>
				</div>
				<div id="page-container">
					<?php
					
					$site_notice = common_config('site', 'notice');
					if(!empty($site_notice)) {
						print '<div id="site-notice">'.common_config('site', 'notice').'</div>';
						}														
					
					?><div class="front-welcome-text <?php if ($registrationsclosed) { print 'registrations-closed'; } ?>">
						<h1></h1>
						<p></p>
					</div>		
					<div id="user-container" style="display:none;">		
						<div id="login-content">
							<form id="form_login" class="form_settings" action="<?php print common_local_url('qvitterlogin'); ?>" method="post">
								<div id="username-container">
									<input id="nickname" name="nickname" type="text" value="<?php print $logged_in_user_nickname ?>" tabindex="1" />
								</div>
								<table class="password-signin"><tbody><tr>
									<td class="flex-table-primary">
										<div class="placeholding-input">
											<input id="password" name="password" type="password" tabindex="2" value="" />
										</div>
									</td>
									<td class="flex-table-secondary">
										<button class="submit" type="submit" id="submit-login" tabindex="4"></button>
									</td>
								</tr></tbody></table>
								<div id="remember-forgot">
									<input type="checkbox" id="rememberme" name="rememberme" value="yes" tabindex="3" checked="checked"> <span id="rememberme_label"></span> · <a id="forgot-password" href="<?php print $instanceurl ?>main/recoverpassword" ></a>
									<input type="hidden" id="token" name="token" value="<?php print common_session_token(); ?>">					
									<?php 

									if(!common_config('plugins','disable-OpenID')) {
										print '<a href="'.$instanceurl.'main/openid" id="openid-login" title="OpenID" donthijack>OpenID</a>';
										}
										
									?>
								</div>
							</form>
						</div>
						<?php
						if($registrationsclosed === false && $client_ip_is_blocked === false) {
						?><div class="front-signup">
							<h2></h2>
							<div class="signup-input-container"><input placeholder="" type="text" name="user[name]" autocomplete="off" class="text-input" id="signup-user-name"></div>
							<div class="signup-input-container"><input placeholder="" type="text" name="user[email]" autocomplete="off" id="signup-user-email"></div>
							<div class="signup-input-container"><input placeholder="" type="password" name="user[user_password]" class="text-input" id="signup-user-password"></div>
							<button id="signup-btn-step1" class="signup-btn" type="submit"></button>
							<div id="other-servers-link"></div>
						</div><?php } ?>
						<div id="user-header">
							<div id="mini-edit-profile-button"></div>
							<div class="profile-header-inner-overlay"></div>						
							<div id="user-avatar-container"><img id="user-avatar" src="" /></div>
							<div id="user-name"></div>
							<div id="user-screen-name"></div>					
						</div>
						<ul id="user-body">
							<li><a id="user-queets"><span class="label"></span><strong></strong></a></li>
							<li><a id="user-following"><span class="label"></span><strong></strong></a></li>
							<li><a id="user-groups"><span class="label"></span><strong></strong></a></li>									
						</ul>				
						<div id="user-footer">
							<div id="user-footer-inner">
								<div id="queet-box" class="queet-box queet-box-syntax" data-start-text=""></div>
								<div class="syntax-middle"></div>
								<div class="syntax-two" contenteditable="true"></div>							
								<div class="mentions-suggestions"></div>			
								<div class="queet-toolbar">
									<div class="queet-box-extras">
										<button class="upload-image"></button>
                                        <!-- UPLOAD SGF FILE GOTABULO -->
                                        <?php if(defined('GOTABULO')) { ?>
                                            <button class="upload-sgf">SGF</button>
                                        <?php } ?>										
										<button class="shorten disabled">URL</button>
									</div>
									<div class="queet-button">
										<span class="queet-counter"></span>
										<button></button>
									</div>						
								</div>
							</div>
						</div>										
						<div class="menu-container">
							<a class="stream-selection friends-timeline" data-stream-header="" data-stream-name="statuses/friends_timeline.json"><i class="chev-right"></i></a>
							<a class="stream-selection notifications" data-stream-header="" data-stream-name="qvitter/statuses/notifications.json"><span id="unseen-notifications"></span><i class="chev-right"></i></a>											
							<a class="stream-selection mentions" data-stream-header="" data-stream-name="statuses/mentions.json"><i class="chev-right"></i></a>				
							<a class="stream-selection my-timeline" data-stream-header="@statuses/user_timeline.json" data-stream-name="statuses/user_timeline.json"><i class="chev-right"></i></a>				
							<a class="stream-selection favorites" data-stream-header="" data-stream-name="favorites.json"><i class="chev-right"></i></a>									
							<a href="<?php print $instanceurl ?>" class="stream-selection public-timeline" data-stream-header="" data-stream-name="statuses/public_timeline.json"><i class="chev-right"></i></a>
							<a href="<?php print $instanceurl ?>main/all" class="stream-selection public-and-external-timeline" data-stream-header="" data-stream-name="statuses/public_and_external_timeline.json"><i class="chev-right"></i></a>					
						</div>
						<div class="menu-container" id="history-container"></div>				
						<div id="qvitter-notice"><?php print common_config('site', 'qvitternotice'); ?></div>																
					</div>						
					<div id="feed">
						<div id="feed-header">
							<div id="feed-header-inner">
								<h2></h2>
								<div class="reload-stream"></div>								
							</div>
						</div>
						<div id="new-queets-bar-container" class="hidden"><div id="new-queets-bar"></div></div>
						<div id="feed-body"></div>
					</div>
			
					<div id="footer"><div id="footer-spinner-container"></div></div>
				</div>
				<script type="text/javascript" src="<?php print $qvitterpath; ?>js/lib/jquery-2.1.1.min.js"></script>
				<script type="text/javascript" src="<?php print $qvitterpath; ?>js/lib/jquery-ui-1.10.3.min.js"></script>
				<script type="text/javascript" src="<?php print $qvitterpath; ?>js/lib/jquery.minicolors.min.js"></script>	    	    
				<script type="text/javascript" src="<?php print $qvitterpath; ?>js/lib/jquery.jWindowCrop.js"></script>	
				<script type="text/javascript" src="<?php print $qvitterpath; ?>js/lib/load-image.min.js"></script>
				<script type="text/javascript" src="<?php print $qvitterpath; ?>js/dom-functions.js?changed=<?php print date('YmdHis',filemtime(QVITTERDIR.'/js/dom-functions.js')); ?>"></script>
				<script type="text/javascript" src="<?php print $qvitterpath; ?>js/misc-functions.js?changed=<?php print date('YmdHis',filemtime(QVITTERDIR.'/js/misc-functions.js')); ?>"></script>
				<script type="text/javascript" src="<?php print $qvitterpath; ?>js/ajax-functions.js?changed=<?php print date('YmdHis',filemtime(QVITTERDIR.'/js/ajax-functions.js')); ?>"></script>
				<script type="text/javascript" src="<?php print $qvitterpath; ?>js/qvitter.js?changed=<?php print date('YmdHis',filemtime(QVITTERDIR.'/js/qvitter.js')); ?>"></script>
				
				<!-- SCRIPS FOR GOTABULO -->
                <script type="text/javascript" src="<?php print $qvitterpath; ?>js/wgo.min.js"></script>
                <script type="text/javascript" src="<?php print $qvitterpath; ?>js/wgo.player.min.js"></script>				
				
				<?php
				
					// event for other plugins to add scripts to qvitter
					Event::handle('QvitterEndShowScripts', array($this));
				
					// we might have custom javascript in the config file that we want to add
					if(QvitterPlugin::settings('js')) {
						print '<script type="text/javascript">'.QvitterPlugin::settings('js').'</script>';
					}				
				
				?>	
			</body>
		</html>

	
			<?php
    }

}

