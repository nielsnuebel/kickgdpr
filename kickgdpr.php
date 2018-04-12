<?php
/**
 * @package    KickGDPR
 * @copyright  2018 Niels Nübel
 * @license    This software is licensed under the MIT license: http://opensource.org/licenses/MIT
 * @link       https://www.niels-nuebel.de
 */

defined('_JEXEC') or die;

/**
 * Plugin class to modify an Article object
 *
 * @since  3.1
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

	public function __construct(& $subject, $config)
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

	function onBeforeCompileHead() {
		if (!$this->app->isSite())
		{
			return true;
		}

		if (!$ga_code = $this->params->get('ga_code', false))
		{
			return true;
		}

		// Add Google Analytics to Head

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
		$js[] = "	alert('" . JText::_('PLG_SYSTEM_KICKGDPR_INFO_GA_OPTOUT_TEXT'). "');"; // Übersetzen
		$js[] = "}";
		$js[] = "";
		$js[] = "if (!window[disableStr]) {";
		$js[] = "<!-- Google Analytics -->";
		$js[] = "(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){";
		$js[] = "(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),";
		$js[] = "m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)";
		$js[] = "})(window,document,'script','https://www.google-analytics.com/analytics.js','__kickgaTracker');";
		$js[] = "";
		$js[] = "__kickgaTracker('create', '" . $ga_code . "', 'auto')";

		if ($this->params->get('ga_forceSSL', false))
		{
			$js[] = "__kickgaTracker('set', 'forceSSL', true);";
		}

		if ($this->params->get('ga_anonymizeIp', true))
		{
			$js[] = "__kickgaTracker('set', 'anonymizeIp', true);";
		}

		if ($this->params->get('ga_displayfeatures', false))
		{
			$js[] = "__kickgaTracker('require', 'displayfeatures');";
		}

		if ($this->params->get('ga_linkid', false))
		{
			$js[] = "__kickgaTracker('require', 'linkid', 'linkid.js');";
		}

		$js[] = "__kickgaTracker('send', 'pageview');";
		$js[] = "}";
		$js[] = "";

		$js[] = "<!-- End Google Analytics -->";


		$headjs = implode("\n",$js);

		$this->doc->addScriptDeclaration($headjs);
	}

	/**
	 * Plugin that loads Ads within content
	 *
	 * @param   string   $context   The context of the content being passed to the plugin.
	 * @param   object   &$article  The article object.  Note $article->text is also available
	 * @param   mixed    &$params   The article params
	 * @param   integer  $page      The 'page' number
	 *
	 * @return  mixed   true if there is an error. Void otherwise.
	 *
	 * @since   1.6
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		if (!$this->app->isSite())
		{
			return true;
		}

		if ($context != 'com_content.article')
		{
			return true;
		}

		// Simple performance check to determine whether bot should process further
		if (strpos($article->text, '{kickgdpr_ga_optout}') === false && strpos($article->text, '{/kickgdpr_ga_optout}') === false)
		{
			return true;
		}

		$ga_optout_openlink = '<a href="#" onClick="__kickgaTrackerOptout(); return false;" >';
		$ga_optout_closelink = '</a>';

		$article->text = str_replace('{kickgdpr_ga_optout}', $ga_optout_openlink, $article->text);
		$article->text = str_replace('{/kickgdpr_ga_optout}', $ga_optout_closelink, $article->text);
	}
}