<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.kickgdpr
 * @author      Niels Nübel <niels@kicktemp.com>
 * @copyright   2018 Kicktemp UG (haftungsbeschraenkt)
 * @license     GNU/GPLv3 <http://www.gnu.org/licenses/gpl-3.0.de.html>
 * @link        https://kicktemp.com
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\Registry\Registry;

/**
 * Form Field class for Kubik-Rubik Joomla! Extensions.
 * Provides a donation code check.
 * credits Viktor Vogel
 */
class JFormFieldKickDonation extends JFormField
{
	protected $type = 'kickdonation';

	protected function getInput()
	{
		$html = '<a class="btn btn-success" href="https://www.paypal.me/kicktemp/5" target="_blank"><span class="icon-smiley-2 icon-white" aria-hidden="true"></span> 5 €</a> <a class="btn btn-success" href="https://www.paypal.me/kicktemp/10" target="_blank"><span class="icon-thumbs-up icon-white" aria-hidden="true"></span> 10 €</a> <a class="btn btn-success" href="https://www.paypal.me/kicktemp/" target="_blank"><span class="icon-star icon-white" aria-hidden="true"></span> # €</a>';
		return $html;
	}

	protected function getLabel()
	{
		return JText::_('KICKDONATION');
	}
}
