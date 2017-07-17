<?php
/*
	Plugin Name: DaWanda-Shop f&uuml;r WordPress
	Plugin URI: http://dmke.de
	Version: 2.0
	Description: Das Dawanda-Plugin zeigt Artikel aus dem eigenen <a href="http://dawanda.com">Dawanda</a>-Shop an. Jetzt Widgetf&auml;hig!
	Author: Dominik Menke
	Author URI: http://dmke.de/	
*/

class Dawanda {
	private $options;
	private $xml;
	
	public $error;
	public $data;
	
	public function __construct($options) {
		$defaults = array(
			'username' => 'DMKE',
			'lang' => 'de',
			'remove-utm' => true,
			'autofetch' => true
		);
		if (!(isset($options['username']) && !empty($options['username']))) {
			$options['username'] = $defaults['username'];
		}
		if (!(isset($options['lang']) && !empty($options['lang']))) {
			$options['lang'] = $defaults['lang'];
		}
		if (!isset($options['remove-utm'])) {
			$options['remove-utm'] = $defaults['remove-utm'];
		}
		if (!isset($options['autofetch'])) {
			$options['autofetch'] = $defaults['autofetch'];
		}
		$this->options = $options;
		$this->error = false;
		if ($options['autofetch']) {
			$this->fetch_data();
		}
	}

	private function remove_utm($string) {
		$pos = strpos($string, '?');
		if ($pos > 0) {
			return substr($string, 0, $pos);
		} else {
			return $string;
		}
	}
	
	public function fetch_data() {
		$url = 'http://'. $this->options['lang'] .'.dawanda.com/widget/shop?username='. $this->options['username'];
		try {
			$this->xml = simplexml_load_file($url);
			$data['shop']['title'] = $this->xml->widget->title .'';
			$data['shop']['user'] = $this->xml->footer->text .'';
			$data['shop']['link'] = $this->options['remove-utm']
				? $this->remove_utm($this->xml->footer->link .'')
				: $this->xml->footer->link .'';
			$data['item'] = array();
			foreach ($this->xml->content->item as $item) {
				$data['item'][] = array(
					'id'       => $item->id .'',
					'title'    => $item->title .'',
					'img_list' => $item->img .'',
					'img_big'  => str_replace('/listview/', '/big/', $item->img .''),
					'link'     => $this->options['remove-utm']
						? $this->remove_utm($item->link .'')
						: $item->link .'',
					'currency' => $item->currency .'',
					'price'    => $item->price .''
				);
			}
			$this->data = $data;
		} catch (Exception $e) {
			$this->error = $e->getMessage();
		}
	}
}

function dmke_dawanda_init() {
	if (function_exists('simplexml_load_file')) {
		add_action('admin_menu', 'dmke_dawanda_admin');
		load_plugin_textdomain('dawanda', 'wp-content/plugins/dmke-dawanda/lang/');
		register_sidebar_widget('DaWanda Shop', 'dmke_dawanda_widget_output', 'dawanda');
		register_widget_control('DaWanda Shop', 'dmke_dawanda_widget_control');
	} else {
		add_action('admin_notices', 'dmke_dawanda_warning');
	}
}

function dmke_dawanda_warning() {
	echo '<div id="dmke-dawanda-warning" class="updated fade"><p><strong>DaWanda Shop Plugin</strong><br />'
		. __('Sorry, your PHP installation does not support native XML parsing. Please disable this Plugin or contact your provider: This plugin requires PHP 5 with SimpleXML support.', 'dawanda')
		.'</p></div>';
}

function dmke_dawanda_widget_output($options) {
	dmke_dawanda($options, true);
}

function dmke_dawanda_template_tag() {
	dmke_dawanda(array(), false);
}

function dmke_dawanda($options, $is_widget = true) {
	extract($options);
	if (!function_exists('simplexml_load_file')) {
		return;
	}

	$o = get_option('dmke_dawanda');
	$dawanda = new Dawanda(
		array(
			'lang'       => $o['lang'],
			'username'   => $o['username'],
			'remove-utm' => $o['remove-utm'] == 0
				? false
				: true
		)
	);
	if ($dawanda->error) {
		return;
	}

	$o['html1'] = stripslashes($o['html1']);
	$o['html2'] = stripslashes($o['html2']) ."\n";
	$o['html3'] = "\n\t". stripslashes($o['html3']) ."\n";
	$o['html4'] = "\n\t". stripslashes($o['html4']) ."\n";
	if ($is_widget) {
		$o['html1'] = $before_widget . $before_title . $dawanda->data['shop']['title'] . $after_title . $o['html1'];
		$o['html2'] .= $after_widget;
	}
	$out = $o['html1'];
	$i = 0;
	foreach ($dawanda->data['item'] as $item) {
		if ($i < $o['limit']) {
			$out .= $o['html3'] .'<div class="dmke-dawanda-item">
	<a class="dmke-dawanda-picture-link" href="'. htmlspecialchars($item['img_big']) .'" rel="lightbox[dawanda]" title="'. htmlspecialchars($item['title']) .'">
		<img src="'. htmlspecialchars($item['img_list']) .'" alt="'. $item['title'] .'" />
	</a>
'. ($o['show-price'] ? '<span class="dmke-dawanda-price">'. $item['currency'] .' '. $item['price'] .'</span>' : '')
.'	<a class="dmke-dawanda-shop-link" href="'. htmlspecialchars($item['link']) .'">'. stripslashes($o['more']) .'</a>
</div>';
		}
		++$i;
	};
	$out .= $o['html2'];
	
	echo $out;
}

function dmke_dawanda_admin() {
	if (function_exists('add_submenu_page')) {
		add_submenu_page(
			'plugins.php',
			__('Dawanda shop configuration', 'dawanda'),
			__('Dawanda configuration', 'dawanda'),
			'manage_options',
			__FILE__,
			'dmke_dawanda_admin_form'
		);
	}
}

function dmke_dawanda_admin_form() {
	$o = array(
		'username' => 'DMKE',
		'limit' => '10',
		'lang'  => 'de',
		'html1' => '<ul id="dmke-dawanda-list">',
		'html2' => '</ul>',
		'html3' => '<li>',
		'html4' => '</li>',
		'more'  => __('more&hellip;', 'dawanda'),
		'show-price' => false,
		'remove-utm' => false
	);
	add_option('dmke_dawanda', $o, 'Options for the Dawanda-shop/ Optionen f&uuml;r den Dawanda-Shop', 'yes');
	
	$o = get_option('dmke_dawanda');
	
	if (isset($_POST['submit'])) {
		$o['username']		= trim($_POST['dmke-dawanda-username']);
		$o['limit']			= trim($_POST['dmke-dawanda-limit']);
		$o['lang']			= $_POST['dmke-dawanda-lang'];
		$o['show-price']	= ($_POST['dmke-dawanda-show-price'] == '1' ? 1 : 0);
		$o['html1']			= trim($_POST['dmke-dawanda-html1']);
		$o['html2']			= trim($_POST['dmke-dawanda-html2']);
		$o['html3']			= trim($_POST['dmke-dawanda-html3']);
		$o['html4']			= trim($_POST['dmke-dawanda-html4']);
		$o['more']			= trim($_POST['dmke-dawanda-more']);
		$o['remove-utm']	= ($_POST['dmke-dawanda-remove-utm'] == '1' ? 1 : 0);

		update_option('dmke_dawanda', $o);
	}
?>
<div class="wrap">
	<h2><?php _e('<a href="http://dawanda.com">DaWanda</a> shop options', 'dawanda'); ?></h2>
	<form name="dmke-dawanda" method="post">
		<input type="hidden" name="dmke-dawanda-options" value="1" />
		<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('dmke-dawanda-form'); ?>

		<h3><?php _e('General options', 'dawanda'); ?></h3>
		
		<table class="form-table" id="dmke-dawanda-table-1">
			<tr>
				<th valign="top" class="scope">
					<?php _e('Your DaWanda user name:', 'dawanda'); ?><br />
				</th>
				<td align="left" valign="top">
					<input name="dmke-dawanda-username" type="text" value="<?php echo htmlspecialchars(stripslashes($o['username'])); ?>" />
					<br />
					<?php _e('You should insert your DaWanda user/shop name. If you don\'t, "DMKE" will be used instead.', 'dawanda') ?>
				</td>
			</tr>

			<tr>
				<th valign="top" class="scope">
					<?php _e('How many products shall be displayed?', 'dawanda'); ?>
				</th>
				<td align="left" valign="top">
					<input name="dmke-dawanda-limit" type="text" value="<?php echo htmlspecialchars(stripslashes($o['limit'])); ?>" />
				</td>
			</tr>

			<tr>
				<th valign="top" class="scope">
					<?php _e('Where are you registered at dawanda.com?', 'dawanda'); ?>
				</th>
				<td align="left" valign="top">
					<label>
						<input name="dmke-dawanda-lang" type="radio" value="de"<?php echo $o['lang'] == 'de' ? ' checked="checked"' : ''; ?> />
						<?php _e('DaWanda Germany', 'dawanda'); ?>
					</label>
					<br />
					<label>
						<input name="dmke-dawanda-lang" type="radio" value="en"<?php echo $o['lang'] == 'en' ? ' checked="checked"' : ''; ?> />
						<?php _e('DaWanda United Kingdom', 'dawanda'); ?>
					</label>
					<br />
					<label>
						<input name="dmke-dawanda-lang" type="radio" value="fr"<?php echo $o['lang'] == 'fr' ? ' checked="checked"' : ''; ?> />
						<?php _e('DaWanda France', 'dawanda'); ?>
					</label>
				</td>
			</tr>
		</table>

		<h3><?php _e('Display options', 'dawanda'); ?></h3>
		
		<p>
			<?php _e('Hint: If you like to use this plugin as a Widget, you should leave the HTML options on the defaults.', 'dawanda'); ?>
		</p>
		
		<table class="form-table" id="dmke-dawanda-table-2">
			<tr>
				<th valign="top" class="scope">
					<?php _e('This HTML will be around all products:', 'dawanda'); ?>
				</th>
				<td align="left" valign="top">
					<input name="dmke-dawanda-html1" type="text" value="<?php echo htmlspecialchars(stripslashes($o['html1'])); ?>" /> <?php _e('before and', 'dawanda'); ?><br />
					<input name="dmke-dawanda-html2" type="text" value="<?php echo htmlspecialchars(stripslashes($o['html2'])); ?>" /> <?php _e('after', 'dawanda'); ?>.
				</td>
			</tr>
			
			<tr>
				<th valign="top" class="scope">
					<?php _e('This HTML will be around a single product:', 'dawanda'); ?>
				</th>
				<td align="left" valign="top">
					<input name="dmke-dawanda-html3" type="text" value="<?php echo htmlspecialchars(stripslashes($o['html3'])); ?>" /> <?php _e('before and', 'dawanda'); ?><br />
					<input name="dmke-dawanda-html4" type="text" value="<?php echo htmlspecialchars(stripslashes($o['html4'])); ?>" /> <?php _e('after', 'dawanda'); ?>.
				</td>
			</tr>
			
			<tr>
				<th valign="top" class="scope">
					<?php _e('Link with this text to the product:', 'dawanda'); ?>
				</th>
				<td align="left" valign="top">
					<input name="dmke-dawanda-more" type="text" value="<?php echo htmlspecialchars(stripslashes($o['more'])); ?>" /><br />
					<?php _e('You also can enter an image tag (<code>&lt;img src="" /&gt;</code>)', 'dawanda'); ?>
				</td>
			</tr>
			
			<tr>
				<th valign="top" class="scope">
					<?php _e('Shall the product price be displayed?', 'dawanda'); ?><br />
				</th>
				<td align="left" valign="top">
					<label>
						<input name="dmke-dawanda-show-price" type="radio" value="1"<?php echo $o['show-price'] == 1 ? ' checked="checked"' : ''; ?> />
						<?php _e('yes', 'dawanda'); ?>
					</label>
					<br />
					<label>
						<input name="dmke-dawanda-show-price" type="radio" value="0"<?php echo $o['show-price'] == 0 ? ' checked="checked"' : ''; ?> />
						<?php _e('no', 'dawanda'); ?>
					</label>
				</td>
			</tr>
			
			<tr>
				<th valign="top" class="scope">
					<?php _e('Remove Google Analytics statistics?', 'dawanda'); ?><br />
				</th>
				<td align="left" valign="top">
					<label>
						<input name="dmke-dawanda-remove-utm" type="radio" value="1"<?php echo $o['remove-utm'] == 1 ? ' checked="checked"' : ''; ?> />
						<?php _e('yes', 'dawanda'); ?>
					</label>
					<br />
					<label>
						<input name="dmke-dawanda-remove-utm" type="radio" value="0"<?php echo $o['remove-utm'] == 0 ? ' checked="checked"' : ''; ?> />
						<?php _e('no', 'dawanda'); ?>
					</label>
					<br />
					<?php _e('By default DaWanda delivers the links to your products with some statistical information you may not benefit from. If you like you can disable this here by clicking "yes".', 'dawanda'); ?>
				</td>
			</tr>
		</table>
		
		<div class="submit">
			<input type="submit" name="submit" value="<?php _e('Update Options') ?> &raquo;" />
		</div>
	</form>
</div>

<div class="wrap">
	<h2><?php _e('Preview', 'dawanda'); ?></h2>
	<p>
		<?php _e('This list is the result of the above settings. To format the presentation, these CSS classes may be used:', 'dawanda'); ?>
	</p>
	<dl>
		<dt><code>div.dmke-dawanda-item</code></dt>
		<dd><?php _e('This is the container which contains a single product.', 'dawanda'); ?></dd>
		<dt><code>div.dmke-dawanda-item a.dmke-dawanda-picture-link</code></dt>
		<dd><?php _e('This is the link around the preview image.', 'dawanda'); ?></dd>
		<dt><code>div.dmke-dawanda-item a.dmke-dawanda-picture-link img</code></dt>
		<dd><?php _e('This is the preview image.', 'dawanda'); ?></dd>
		<dt><code>div.dmke-dawanda-item a.dmke-dawanda-shop-link</code></dt>
		<dd><?php _e('This is the link directing your visitors to the products page at dawanda.com.', 'dawanda'); ?></dd>
		<dt><code>div.dmke-dawanda-item span.dmke-dawanda-price</code></dt>
		<dd><?php _e('If you display the price of your product, this is the selector.', 'dawanda'); ?></dd>
	</dl>
	<p>
		<?php dmke_dawanda_template_tag(); ?>
	</p>
</div>

<p class="wrap" style="text-align: center;">
	(<a href="http://creativecommons.org/licenses/by/2.0/de" title="Lizenz" rel="copyright">cc</a>/<a href="http://www.gnu.org/copyleft/gpl.html" rel="copyright">GPL</a>)
	<a href="http://dmke.de" title="Mein Blog">DM</a><a href="http://dmke.dawanda.com" title="Mein Shop">KE</a>.
</p>
<?php
}

function dmke_dawanda_widget_control() {
	_e('You can manage the options of this widget on the <a href="./plugins.php?page=dmke-dawanda/dmke-dawanda.php">options page</a>', 'dawanda');
}

add_action('init', 'dmke_dawanda_init');

?>