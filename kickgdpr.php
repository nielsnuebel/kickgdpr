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

		if (!$ga_code = $this->params->get('ga_code', false))
		{
			return;
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

		if ($this->params->get('ga_forceSSL', true))
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

		$headjs = implode("\n", $js);

		$this->doc->addScriptDeclaration($headjs);
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
}
