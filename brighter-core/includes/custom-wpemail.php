<?php
defined('ABSPATH') || exit;

/** Sender name and address for all wp_mail */
add_filter('wp_mail_from_name', function($name){ return 'Brighter Websites'; });
add_filter('wp_mail_from', function($email){ return 'no-reply@' . preg_replace('#^www\.#','', parse_url(home_url(), PHP_URL_HOST)); });

/** Comment moderation: subject and message */
add_filter('comment_moderation_subject', function($subj, $comment_id){
  return 'Comment pending review on ' . wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
}, 10, 2);

add_filter('comment_moderation_text', function($msg, $comment_id){
  $c = get_comment($comment_id);
  $post = get_post($c->comment_post_ID);
  $approve_url = admin_url("comment.php?action=approve&c={$comment_id}");
  $trash_url   = admin_url("comment.php?action=trash&c={$comment_id}");
  return sprintf(
    "New comment awaiting moderation on \"%s\"\n\nAuthor: %s\nEmail: %s\nURL: %s\nIP: %s\n\nContent:\n%s\n\nApprove: %s\nTrash: %s\n",
    $post->post_title,
    $c->comment_author,
    $c->comment_author_email,
    $c->comment_author_url ?: '—',
    $c->comment_author_IP,
    $c->comment_content,
    $approve_url,
    $trash_url
  );
}, 10, 2);



/** Password reset emails */
add_filter('retrieve_password_title', function($title){ return 'Reset your password at ' . get_bloginfo('name'); });
add_filter('retrieve_password_message', function($message, $key, $user_login, $user_data){
  $url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
  return "Hi {$user_login},\n\nUse the link below to reset your password:\n{$url}\n\nIf you did not request this, you can ignore this email.\n";
}, 10, 4);



/** New user notifications (to the user) */
add_filter('wp_new_user_notification_email', function($wp_new_user_notification_email, $user, $blogname){
  $wp_new_user_notification_email['subject'] = "Welcome to {$blogname}";
  $wp_new_user_notification_email['message'] = "Hi {$user->user_login},\n\nYour account has been created at {$blogname}.\n";
  return $wp_new_user_notification_email;
}, 10, 3);


/** Optional: stop WordPress emailing the admin on password changes */
add_filter('wp_password_change_notification', '__return_false');


/** Core update noise control */
add_filter('auto_core_update_send_email', function($send, $type, $core_update, $result){
  if ($type === 'success') return false; // only email on failures
  return $send;
}, 10, 4);
