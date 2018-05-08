<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.kickgdpr
 * @author      Niels Nübel <niels@niels-nuebel.de>
 * @copyright   2018 Niels Nübel
 * @license     GNU/GPLv3 <http://www.gnu.org/licenses/gpl-3.0.de.html>
 * @link        https://kicktemp.com
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Class plgSystemKickGDPR
 *
 * Enables Google Analytics functionality and adds an opt-out
 * link to disable it for GDPR law, with setting an cookie.
 *
 * @package     Joomla.Plugin
 * @subpackage  System.kickgdpr
 * @since       3.8
 */
class PlgSystemKickGdpr extends JPlugin
{
	/**
	 * Application object
	 *
	 * @var    JApplicationCms
	 * @since  3.2
	 */
	protected $app;

	/**
	 * \JDocument object
	 *
	 * @var    \JDocument
	 * @since  3.2
	 */
	protected $doc;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Plugin Trigger Content Code
	 *
	 * @var    String
	 * @since  3.2
	 */
	protected $trigger_content = null;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe -- event dispatcher.
	 * @param   object  $config    An optional associative array of configuration settings.
	 *
	 * @since   1.6
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		if (property_exists($this, 'doc'))
		{
			$reflection = new \ReflectionClass($this);

			if ($reflection->getProperty('doc')->isPrivate() === false && $this->doc === null)
			{
				$this->doc = \JFactory::getDocument();
			}
		}
	}

	/**
	 * onBeforeCompileHead
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onBeforeCompileHead()
	{
		if (!$this->app->isSite())
		{
			return;
		}

		$ga_code = $this->params->get('ga_code', false);
		$pixel_id = $this->params->get('pixel_id', false);

		if ((!$ga_code && $this->params->get('disable_ga', false)) && (!$pixel_id && $this->params->get('disable_facebook', false)) && $this->params->get('disable_cookie', false))
		{
			return;
		}

		// Add Google Analytics to Head
		if ($ga_code = $this->params->get('ga_code', false) && !$this->params->get('disable_ga', false))
		{
			$js = array();

			$js[] = "";
			$js[] = "var disableStr = 'ga-disable-" . $ga_code . "';";
			$js[] = "";
			$js[] = "/* Function to detect opted out users */";
			$js[] = "function __kickgaTrackerIsOptedOut() {";
			$js[] = "	return document.cookie.indexOf(disableStr + '=true') > -1;";
			$js[] = "};";
			$js[] = "";
			$js[] = "/* Disable tracking if the opt-out cookie exists. */";
			$js[] = "if ( __kickgaTrackerIsOptedOut() ) {";
			$js[] = "	window[disableStr] = true;";
			$js[] = "};";
			$js[] = "";
			$js[] = "/* Disable tracking if do not track active. */";
			$js[] = "if (navigator.doNotTrack == 1) {";
			$js[] = "	window[disableStr] = true;";
			$js[] = "};";
			$js[] = "";
			$js[] = "function __kickgaTrackerOptout() {";
			$js[] = "   document.cookie = disableStr + '=true; expires=Thu, 31 Dec 2099 23:59:59 UTC; path=/';";
			$js[] = "	window[disableStr] = true;";
			$js[] = "	alert('" . JText::_('PLG_SYSTEM_KICKGDPR_INFO_GA_OPTOUT_TEXT') . "');";
			$js[] = "}";
			$js[] = "";

			$headjs = implode("\n", $js);

			$this->doc->addScriptDeclaration($headjs);
		}

		// Add Cookie Info
		if (!$this->params->get('disable_cookie', false))
		{
			$js_css_source = $this->params->get('js_css_source', 'default');

			if ($js_css_source === 'default')
			{
				$jssrc  = 'plg_system_kickgdpr/cookieconsent.min.js';
				$csssrc = 'plg_system_kickgdpr/cookieconsent.min.css';
			}

			if ($js_css_source === 'cloudflare')
			{
				$jssrc = '//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.6/cookieconsent.min.js';
				$csssrc = '//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.6/cookieconsent.min.css';
			}

			if ($js_css_source === 'cloudflare' || $js_css_source === 'default')
			{
				JHtml::_('script', $jssrc, array('version' => 'auto', 'relative' => true));
				JHtml::_('stylesheet', $csssrc, array('version' => 'auto', 'relative' => true));
			}

			// Settings
			$href = '';
			$banner_color = $this->params->get('banner_color', '#000000');
			$banner_text = $this->params->get('banner_text', '#FFFFFF');
			$button_color = $this->params->get('button_color', '#F1D600');
			$button_text = $this->params->get('button_text', '#000000');
			$position = explode(' ', $this->params->get('cookie_position', 'bottom'));
			$type = $this->params->get('compliance_type', '');
			$theme = $this->params->get('cookie_layout', 'block');
			$message = $this->params->get('message', 'PLG_SYSTEM_KICKGDPR_MESSAGE_DEFAULT');
			$dismiss = $this->params->get('dismiss', 'PLG_SYSTEM_KICKGDPR_DISMISS_DEFAULT');
			$allow = $this->params->get('acceptbutton', 'PLG_SYSTEM_KICKGDPR_ACCEPTBUTTON_DEFAULT');
			$deny = $this->params->get('denybutton', 'PLG_SYSTEM_KICKGDPR_DENYBUTTON_DEFAULT');
			$link = $this->params->get('learnMore', 'PLG_SYSTEM_KICKGDPR_LEARNMORE_DEFAULT');
			$expiryDays = $this->params->get('expiryDays', 365);
			$customcode = $this->params->get('customcode', false);

			$lang_links = $this->params->get('lang_links', false);

			if ($lang_links && count($lang_links))
			{
				$lang = $this->app->getLanguage()->getTag();

				foreach ($lang_links as $lang_link)
				{
					if ($lang_link->language === '*' || $lang_link->language === $lang)
					{
						$href = $lang_link->link;
						$link_url = $lang_link->link_url;
						$href = (isset($href) && '' != $href) ? JRoute::_("index.php?Itemid={$href}") : false;
						$href = (isset($link_url) && '' != $link_url && !$href) ? $link_url : $href;
					}
				}
			}

			if ($theme == 'wire')
			{
				$border = $button_color;
				$button_color = 'transparent';
			}

			$js = array();
			$js[] = '<!-- Start Cookie Alert -->';
			$js[] = 'window.addEventListener("load", function(){';
			$js[] = 'window.cookieconsent.initialise({';
			$js[] = '  "palette": {';
			$js[] = '    "popup": {';
			$js[] = '      "background": "' . $banner_color . '",';
			$js[] = '      "text": "' . $banner_text . '"';
			$js[] = '    },';
			$js[] = '    "button": {';
			$js[] = '      "background": "' . $button_color . '",';
			$js[] = '      "text": "' . $button_text . '",';

			if (isset($border))
			{
				$js[] = '      "border": "' . $border . '"';
			}

			$js[] = '    }';
			$js[] = '  },';
			$js[] = '  "theme": "' . $theme . '",';
			$js[] = '  "position": "' . $position[0] . '",';

			if (isset($position[1]))
			{
				$js[] = '  "static": true,';
			}

			$js[] = '  "type": "' . $type . '",';
			$js[] = '  "revokeBtn": "<div class=\"cc-revoke {{classes}}\">' . JText::_('PLG_SYSTEM_KICKGDPR_COOKIE_POLICY') . '</div>",';
			$js[] = '  "content": {';
			$js[] = '    "message": "' . JText::_($message) . '",';
			$js[] = '    "dismiss": "' . JText::_($dismiss) . '",';
			$js[] = '    "allow": "' . JText::_($allow) . '",';
			$js[] = '    "deny": "' . JText::_($deny) . '",';
			$js[] = '    "link": "' . JText::_($link) . '",';
			$js[] = '    "href": "' . JText::_($href) . '",';
			$js[] = '  },';
			$js[] = '  "cookie": {';
			$js[] = '    "expiryDays": ' . (int) $expiryDays;
			$js[] = '  },';
			$js[] = '  onInitialise: function (status) {';
			$js[] = '    handleCookies(status);';
			$js[] = '  },';
			$js[] = '  onStatusChange: function (status, chosenBefore) {';
			$js[] = '    handleCookies(status);';
			$js[] = '  },';
			$js[] = '  onRevokeChoice: function () {';
			$js[] = '    handleCookies(status);';
			$js[] = '  }';
			$js[] = '})});';

			$js[] = "<!-- End Cookie Alert -->";
			$js[] = "function handleCookies(status){";

			if ($type != '' && $type == 'opt-out')
			{
				$js[] = '  if (status != "deny") {';
			}

			if ($type != '' && $type == 'opt-in')
			{
				$js[] = '  if (status == "allow") {';
			}

			// Add Google Analytics to Head
			if ($ga_code = $this->params->get('ga_code', false) && !$this->params->get('disable_ga', false))
			{
				$js[] = "    <!-- Google Analytics -->";
				$js[] = "    if (!window[disableStr]) {";
				$js[] = "    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){";
				$js[] = "    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),";
				$js[] = "    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)";
				$js[] = "    })(window,document,'script','https://www.google-analytics.com/analytics.js','__kickgaTracker');";
				$js[] = "";
				$js[] = "    __kickgaTracker('create', '" . $ga_code . "', 'auto')";
				if ($this->params->get('ga_forceSSL', true))
				{
					$js[] = "    __kickgaTracker('set', 'forceSSL', true);";
				}

				if ($this->params->get('ga_anonymizeIp', true))
				{
					$js[] = "    __kickgaTracker('set', 'anonymizeIp', true);";
				}

				if ($this->params->get('ga_displayfeatures', false))
				{
					$js[] = "    __kickgaTracker('require', 'displayfeatures');";
				}

				if ($this->params->get('ga_linkid', false))
				{
					$js[] = "    __kickgaTracker('require', 'linkid', 'linkid.js');";
				}

				$js[] = "    __kickgaTracker('send', 'pageview');";
				$js[] = "    }";
				$js[] = "    <!-- End Google Analytics -->";
				$js[] = "";
			}

			// Add Facebook Pixel Code to Head
			if ($pixel_id && !$this->params->get('disable_facebook', false))
			{
				$js[] = "    <!-- Facebook Pixel Code -->";
				$js[] = "    !function(f,b,e,v,n,t,s)";
				$js[] = "    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?";
				$js[] = "    n.callMethod.apply(n,arguments):n.queue.push(arguments)};";
				$js[] = "    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';";
				$js[] = "    n.queue=[];t=b.createElement(e);t.async=!0;";
				$js[] = "    t.src=v;s=b.getElementsByTagName(e)[0];";
				$js[] = "    s.parentNode.insertBefore(t,s)}(window,document,'script',";
				$js[] = "    'https://connect.facebook.net/en_US/fbevents.js');";
				$js[] = "    fbq('init', '" . $pixel_id . "');";
				$js[] = "    fbq('track', 'PageView');";
				$js[] = "    <!-- End Facebook Pixel Code -->";
				$js[] = "";
			}

			// Add Custom Code from Plugin Params to Head
			if ($customcode && $customcode != '')
			{
				$js[] = '    <!-- Custom Code -->';
				$js[] = '    ' . $customcode;
				$js[] = '    <!-- End Custom Code -->';
			}

			// Add Custom Code from Plugin Trigger onKickGDPR to Head
			$trigger_content = $this->trigger_content;
			if ($trigger_content && $trigger_content != '')
			{
				$js[] = '    <!-- Plugin Trigger Code -->';
				$js[] = '    ' . $trigger_content;
				$js[] = '    <!-- End Plugin Trigger Code -->';
			}
				// $js[] = 'PUT your Code here';

			if ($type != '' && ($type == 'opt-out' || $type == 'opt-in'))
			{
				$js[] = '  }';
			}

			$js[] = "}";
			$js[] = "";
			$js[] = "// Init handleCookies if the user doesn't choose any options";
			$js[] = "if (document.cookie.split(';').filter(function(item) {";
			$js[] = "    return item.indexOf('cookieconsent_status=') >= 0";
			$js[] = "}).length == 0) {";
			$js[] = "  handleCookies('notset');";
			$js[] = "};";

			/*foreach ($js as $key => $jss)
			{
				$js[$key] = ltrim($jss);
			}*/

			$headjs = implode("\n", $js);

			$this->doc->addScriptDeclaration($headjs);
		}
	}

	/**
	 * onContentPrepare
	 *
	 * @param   string   $context   The context of the content being passed to the plugin.
	 * @param   object   &$article  The article object.  Note $article->text is also available
	 * @param   mixed    &$params   The article params
	 * @param   integer  $page      The 'page' number
	 *
	 * @return  void
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		if (!$this->app->isSite())
		{
			return;
		}

		if ($context != 'com_content.article')
		{
			return;
		}

		// Simple performance check to determine whether bot should process further
		if (strpos($article->text, '{kickgdpr_ga_optout}') === false && strpos($article->text, '{/kickgdpr_ga_optout}') === false)
		{
			return;
		}

		$gaOptoutOpenlink = '<a href="#" onClick="__kickgaTrackerOptout(); return false;" >';
		$gaOptoutCloselink = '</a>';

		$article->text = str_replace('{kickgdpr_ga_optout}', $gaOptoutOpenlink, $article->text);
		$article->text = str_replace('{/kickgdpr_ga_optout}', $gaOptoutCloselink, $article->text);
	}

	/**
	 * Plugin Trigger 
	 *
	 * @param string $cookieConsentCode
	 */
	public function onKickGDPR($cookieConsentCode = '')
	{
		if($cookieConsentCode != '')
		{
			$this->trigger_content .= $cookieConsentCode;
		}
	}
}
