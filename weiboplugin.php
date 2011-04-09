<?php
/*
Plugin Name: 新浪微博插件
Plugin URI: http://www.zhlwish.com/wp-plugin-for-sina-weibo
Description: 一个简单的在WordPress边栏显示本人发布的最近新浪微博的插件，支持Widget拖拽方式设置插件显示的位置
Author: Liang ZHOU (http://www.zhlwish.com)
Version: 0.1
Author URI: http://www.zhlwish.com
*/
session_start();
//session_destroy();
//session_unset();
define(APP_KEY, '2889102141');
define(APP_SECRET, '1189e8b211a55102cea2a9a9539084b1');
include_once('weibooauth.php');include_once('weibolib.php');
class WeiboPlugin{    
    /**
     * 获取存放在数据库中的配置信息
     */ 
	function get_weibo_options()
	{
		$oauth_token = get_option('weibo_oauth_token', '');
		$oauth_secret = get_option('weibo_oauth_secret', '');
		if(empty($oauth_token))
		{
			return false;
		}
		else
		{
			return array('key'=>$oauth_token, 'secret'=>$oauth_secret);
		}
	}
	function weibo_session_end($oauth_token, $oauth_secret)
	{
		$client = new WeiboClient( APP_KEY, APP_SECRET, $oauth_token, $oauth_secret);
		$msg = $client->end_session();
		
		if ($msg === false || $msg === null)
		{
			echo "Error occured";
			return false;
		}
		if (isset($msg['name']))
		{
			delete_option('weibo_oauth_token');
			delete_option('weibo_oauth_secret');
		}
	}
	function get_access_token()
	{
		$oauth = new WeiboOAuth(APP_KEY, APP_SECRET, $_SESSION['request']['oauth_token'], $_SESSION['request']['oauth_token_secret']);
		$token = $oauth->getAccessToken( $_REQUEST['oauth_verifier']);
		if($token)
		{
			update_option('weibo_oauth_token', $token['oauth_token']);
			update_option('weibo_oauth_secret', $token['oauth_token_secret']);
		}
	}
	/**
	 * 输出微博内容
	 */
	function output_tweets($oauth_token , $oauth_secret, $tweet_count=5)
	{
		$c = new WeiboClient( APP_KEY , APP_SECRET , $oauth_token , $oauth_secret );
		$ms  = $c->user_timeline(1, $tweet_count);
		$me = $c->verify_credentials();
		?>
		<ul id="weibo">
		<?php foreach( $ms as $item ){ ?>
		<li>
			<?php
			$text = $item['text'];
			echo $this->format_tweet($text);
			
			$format = human_readable_time($item['created_at']);            $tweet_url = 'http://api.weibo.com/'.$item['user']['id'].'/statuses/'.$item['id'].'?source='.APP_KEY;			echo "<a href='$tweet_url' class='weibo_link' target='_blank'>${format}前</a>";
			?>
		</li>
		<?php }?>
		</ul>
		<?php
	}
	private function format_tweet($tweet_msg)
	{        $tweet_msg = add_topic_link($tweet_msg);
        $tweet_msg = add_url_link($tweet_msg);        $tweet_msg = add_at_link($tweet_msg);
		return $tweet_msg;
	}
	/**
	 * 生成授权url
	 */
	function output_authorize_url($callback_url)
	{
		$oauth = new WeiboOAuth(APP_KEY, APP_SECRET);	
		if(! isset($_SESSION['request']))
		{
			$token = $oauth->getRequestToken();
			$_SESSION['request'] = $token;
		}
		$authorizeUrl = $oauth->getAuthorizeURL($_SESSION['request'], false, $callback_url);
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>新浪微博</h2>
			<p>
				<a target="_blank" href="http://login.sina.com.cn/regagreement.html">新浪网络服务使用协议</a>
			</p>
			<p>
				<a href="<?=$authorizeUrl?>">授权</a>
			</p>
		</div>
		<?php
	}
}
function weibo_plugin_admin()
{
	$token = WeiboPlugin::get_weibo_options();
	$callback_url = "http://".$_SERVER ['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	//点击了取消授权按钮
	if($_POST['action'] == 'end_session')
	{
		WeiboPlugin::weibo_session_end($token['key'], $token['secret']);
		unset($_SESSION['request']);
	}
	//点击了授权按钮返回wp
	if(isset($_REQUEST['oauth_verifier']))
	{
		WeiboPlugin::get_access_token();
	}
	//已经获取了新浪微博的授权
	$token = WeiboPlugin::get_weibo_options();
	if($token)
	{
		$c = new WeiboClient( APP_KEY , APP_SECRET , $token['key'] , $token['secret'] );
		$ms  = $c->user_timeline();
		$me = $c->verify_credentials();
		?>
		<h2>新浪微博</h2>
		<p>授权新浪用户名：<?=$me['name']?></p>
		<form action="" method="POST">
			<input type="hidden" name="action" value="end_session"/>
			<input type="submit" value="修改授权帐号" />
		</form>
		<?php
	}
	else
	{
		WeiboPlugin::output_authorize_url($callback_url);
	}
}
/**
 * 微博后台管理小工具，可通过拖拽以及简单的配置对微博列表显示的位置进行设置
 */
class Weibo_Widget extends WP_Widget
{
	function Weibo_Widget()
	{
		parent::WP_Widget(false, $name = '新浪微博');
	}
	function widget($args, $instance)
	{
		extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
		echo $before_widget;
		if ( $title )        {
			echo $before_title . $title . $after_title;
		}        
		$weibo = new WeiboPlugin();
		$opts = $weibo->get_weibo_options();
		$weibo->output_tweets($opts['key'], $opts['secret'], $instance['tweet_count']); 
		echo $after_widget;
	}
	function form($instance)
	{
		$title = esc_attr($instance['title']);
		$tweet_count = esc_attr($instance['tweet_count']);
        ?>
            <p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
			</p>
            <p>
				<label for="<?php echo $this->get_field_id('tweet_count'); ?>"><?php _e('显示微博条数:'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('tweet_count'); ?>" name="<?php echo $this->get_field_name('tweet_count'); ?>" type="text" value="<?php echo $tweet_count; ?>" />				
			</p>
        <?php 
	}
	function update($new_instance, $old_instance)
	{
        $instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['tweet_count'] = strip_tags($new_instance['tweet_count']);
        return $instance;
	}
}
//注册新浪微博小工具
add_action('widgets_init', create_function('', 'return register_widget("Weibo_Widget");'));
/**
 * 微博后台管理页面菜单
 */
function weibo_admin_page()
{
    add_submenu_page('plugins.php', '新浪微博', '新浪微博', 'manage_options', 'weibo-auth', 'weibo_plugin_admin');
}
//向系统菜单注册“新浪微博”菜单项
add_action('admin_menu', 'weibo_admin_page');
/**
 * 添加微博列表的样式表
 */
function weibo_include_css()
{
?>
<link rel="stylesheet" type="text/css" media="all" href="<?= WP_PLUGIN_URL.'/sinaweibo/'?>weibo.css" />
<?php
}
//注册微博列表的样式
add_action('wp_head', 'weibo_include_css');
?>