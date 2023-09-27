<?php

function chatgpt_handle_bulk_action($redirect_to, $action, $post_ids) {
    if ($action === 'chatgpt_recreate_texts') {
        if ( chatgpt_freemius_integration()->can_use_premium_code() ) {
            $api_key = get_option('chatgpt_api_key');
            $saved_prompt = get_option('chatgpt_saved_prompt', 'Escreva um artigo sobre {palavra-chave}');

            foreach ($post_ids as $post_id) {
                $post = get_post($post_id);
                $complete_prompt = str_replace('{palavra-chave}', $post->post_title, $saved_prompt);
                $new_text = chatgpt_generate_text($api_key, $complete_prompt);

                $updated_post = array(
                    'ID' => $post_id,
                    'post_content' => $new_text
                );

                wp_update_post($updated_post);
            }

            $redirect_to = add_query_arg('chatgpt_recreated_texts', count($post_ids), $redirect_to);
        } else {
            $redirect_to = add_query_arg('chatgpt_recreated_texts_failed', 1, $redirect_to);
        }
    }

    return $redirect_to;
}

function chatgpt_add_recreate_text_link($actions, $post) {
    if ( chatgpt_freemius_integration()->can_use_premium_code() ) {
        $actions['recreate_text'] = '<a href="#" data-post-id="' . esc_attr($post->ID) . '" class="chatgpt-recreate-text" onclick="recreate_text(this)">Recriar texto</a><span class="chatgpt-loading" style="display:none;"><img src="https://gptautopost.com/wp-content/plugins/chatgpt-post-creator/load.gif" alt="Carregando..." /></span>';
    } else {
        $actions['recreate_text'] = '<span>Recriar texto (Versão Premium)</span>';
    }
    return $actions;
}


function chatgpt_enqueue_admin_scripts($hook) {
    if ($hook !== 'edit.php') {
        return;
    }

    wp_enqueue_script('chatgpt-admin', plugin_dir_url(__FILE__) . 'chatgpt-admin.js', array('jquery'), '1.0.0', true);

    $ajax_object = array(
        'ajax_nonce' => wp_create_nonce('chatgpt-ajax-nonce'),
        'ajax_url' => admin_url('admin-ajax.php')
    );

    wp_localize_script('chatgpt-admin', 'chatgpt_ajax_object', $ajax_object);
}
