<?php 

function add_topic_link($tweet_msg)
{ 
    preg_match_all ( '/#(.*)#/', $tweet_msg, $matches, PREG_SET_ORDER);
    foreach( $matches as $match )
    {
        $url = "http://weibo.com/k/" . rawurlencode($match[1]);
        $tweet_msg = str_replace( '#'.$match[1].'#', "<a href='".$url."' target='_blank'>#".$match[1]."#</a>" , $tweet_msg) ;
    }
    return $tweet_msg;
}

function add_url_link($tweet_msg)
{
    preg_match_all ( '|http://t\.cn/\w+|', $tweet_msg, $matches, PREG_SET_ORDER);
    foreach( $matches as $match )
    {
        $url = $match[0];
        $tweet_msg = str_replace( $match[0], "<a href='".$url."' target='_blank'>".$match[0]."</a>" , $tweet_msg) ;
    }
    return $tweet_msg;
}

function add_at_link($tweet_msg)
{
    preg_match_all ( '/(@.*?):/u', $tweet_msg, $matches, PREG_SET_ORDER);
    foreach( $matches as $match )
    {
        $url = $match[1];
        $tweet_msg = str_replace( $match[1], "<a href='".$url."' target='_blank'>".$match[1]."</a>" , $tweet_msg) ;
    }
    return $tweet_msg;
}

function human_readable_time($time)
{
    $now = strtotime(date('r'));
    $time = strtotime($time);
    $interval = abs($now - $time);

    $format = array();
    if(($val=floor($interval/(60*60*24*365))) != 0) 
    {
        $format = $val.'年';
    }
    else if(($val=floor($interval/(60*60*24*30))) != 0) 
    {
        $format = $val.'月';
    }
    else if(($val=floor($interval/(60*60*24))) != 0) 
    {
        $format = $val.'天';
    }
    else if(($val=round($interval/(60*60))) != 0) 
    {
        $format = $val.'小时';
    }
    else if(($val=floor($interval/60)) != 0) 
    {
        $format = $val.'分钟';
    }
    else if(($val=$interval) != 0) 
    {
        $format = $val.'s秒';
    }
    return $format;
}
?>