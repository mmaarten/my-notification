<?php


namespace My\Notification;

class App
{
    const NONCE_NAME = 'my_page_access_nonce';

    public static function init()
    {
        add_action('template_redirect', [__CLASS__,  'subscribe']);
        add_action('template_redirect', [__CLASS__,  'unsubscribe']);

        add_shortcode('notification-subscribe', function ($atts) {

            global $wp;

            $atts = shortcode_atts([
                'id' => 0,
            ], $atts);

            if (! $atts['id']) {
                return __('id is required.', 'my-notification');
            }

            $post = get_post($atts['id']);

            if (! $post || get_post_type($post) != 'notification') {
                return __('Post is required.', 'my-notification');
            }

            if (! is_user_logged_in()) {
                return __('You need to be logged in.', 'my-notification');
            }

            $user_id = get_current_user_id();

            $data = json_decode($post->post_content, true);

            $recipients = $data['carriers']['email']['recipients'];

            $recipient = false;
            foreach ($recipients as $_recipient) {
                if ($_recipient['recipient'] == $user_id) {
                    $recipient = true;
                    break;
                }
            }

            $url = add_query_arg([
                'post' => $post->ID,
                'user' => $user_id,
                self::NONCE_NAME => wp_create_nonce('my_unsubscribe_notification'),
            ], home_url($wp->request));

            if ($recipient) {
                return sprintf(
                    '<p>%1$s <a class="btn btn-primary" href="%2$s">%3$s</a></p>',
                    __('You are subscribed.', 'my-notification'),
                    $url,
                    __('Unsubscribe', 'my-notification')
                );
            }

            $url = add_query_arg([
                'post' => $post->ID,
                'user' => $user_id,
                self::NONCE_NAME => wp_create_nonce('my_subscribe_notification'),
            ], home_url($wp->request));

            return sprintf(
                '<p>%1$s <a class="btn btn-primary" href="%2$s">%3$s</a></p>',
                __('You are not subscribed.', 'my-notification'),
                $url,
                __('Subscribe', 'my-notification')
            );
        });
    }

    public static function subscribe()
    {
        if (empty($_GET[self::NONCE_NAME])) {
            return;
        }

        if (! wp_verify_nonce($_GET[self::NONCE_NAME], 'my_subscribe_notification')) {
            return;
        }

        $post = isset($_GET['post']) ? get_post($_GET['post']) : null;
        $user = isset($_GET['user']) ? get_user_by('ID', $_GET['user']) : null;

        if (! $post || get_post_type($post) != 'notification') {
            wp_die(__('Invalid post.', 'my-notification'));
        }

        if (! $user) {
            wp_die(__('Invalid user.', 'my-notification'));
        }

        $data = json_decode($post->post_content, true);

        $recipients = &$data['carriers']['email']['recipients'];

        // Double check
        $recipient = wp_filter_object_list($recipients, 'recipient', $user_id) ? true : false;

        if ($recipient) {
            return;
        }

        $recipients[] = [
            'type'      => 'user',
            'recipient' => $user->ID
        ];

        wp_update_post([
            'ID'           => $post->ID,
            'post_content' => json_encode($data),
        ]);
    }

    public static function unsubscribe()
    {
        if (empty($_GET[self::NONCE_NAME])) {
            return;
        }

        if (! wp_verify_nonce($_GET[self::NONCE_NAME], 'my_unsubscribe_notification')) {
            return;
        }

        $post = isset($_GET['post']) ? get_post($_GET['post']) : 0;
        $user = isset($_GET['user']) ? get_user_by('ID', $_GET['user']) : 0;

        if (! $post || get_post_type($post) != 'notification') {
            wp_die(__('Invalid post.', 'my-notification'));
        }

        if (! $user) {
            wp_die(__('Invalid user.', 'my-notification'));
        }

        $data = json_decode($post->post_content, true);

        $recipients = $data['carriers']['email']['recipients'];
        $_recipients = [];

        foreach ($recipients as $recipient) {
            if ($recipient['recipient'] != $user->ID) {
                $_recipients[] = $recipient;
            }
        }

        $data['carriers']['email']['recipients'] = $_recipients;

        wp_update_post([
            'ID'           => $post->ID,
            'post_content' => json_encode($data),
        ]);
    }
}
