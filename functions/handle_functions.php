<?php

function chatgpt_handle_bulk_action($redirect_to, $action, $post_ids) {
    $api_key = get_option('chatgpt_api_key');
    $mj_key='25e8290b-bb5e-48ad-a9ac-efe2182fe749';
    if ($action === 'chatgpt_recreate_texts') {
        if ( chatgpt_freemius_integration()->can_use_premium_code() ) {
            
            $saved_prompt = get_option('chatgpt_saved_prompt', 'Escreva um artigo sobre {palavra-chave}');

            foreach ($post_ids as $post_id) {
                $post = get_post($post_id);
                //$complete_prompt = str_replace('{palavra-chave}', $post->post_title, $saved_prompt);
                $new_text = chatgpt_generate_text($api_key, $post->post_title);
               


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

    if($action==='recreate_image_with_dalle'){
        if(chatgpt_freemius_integration()->can_use_premium_code()){
            print_r($mj_key);
            foreach($post_ids as $post_id){
                $post =get_post($post_id);
                generate_image_with_dall_e($api_key,$post->post_title,$post_id);
            }
        }
    }


    if($action==='recreate_image_with_mj'){
        if(chatgpt_freemius_integration()->can_use_premium_code()){
            foreach($post_ids as $post_id){
                $post=get_post($post_id);
                generate_image_with_mj($mj_key,$post->post_title,$post_id);
            }
        }
    }
    return $redirect_to;
}

function chatgpt_add_recreate_text_link($actions, $post) {
    if ( chatgpt_freemius_integration()->can_use_premium_code() ) {
        $actions['recreate_text'] = '<a href="#" data-post-id="' . esc_attr($post->ID) . '" class="chatgpt-recreate-text" onclick="recreate_text(this)">Recriar texto</a><span class="chatgpt-loading" style="display:none;"><img src="https://gptautopost.com/wp-content/plugins/chatgpt-post-creator/load.gif" alt="Carregando..." /></span>';
        $actions['recreate_dalle'] = '<a href="#" data-post-image-id="' . esc_attr($post->ID) . '" class="chatgpt-recreate-dalle" onclick="recreate_image(this)">Recriar imagem com DALLE</a><span class="chatgpt-loading" style="display:none;"><img src="https://gptautopost.com/wp-content/plugins/chatgpt-post-creator/load.gif" alt="Carregando..." /></span>';
        $actions['recreate_mj']='<a href="#" data-post-mj-id="' . esc_attr($post->ID) . '" class="chatgpt-recreate-mj" onclick="recreate_image_mj(this)">Recriar imagem com Midjounal</a><span class="chatgpt-loading" style="display:none;"><img src="https://gptautopost.com/wp-content/plugins/chatgpt-post-creator/load.gif" alt="Carregando..." /></span>';
    } else {
        $actions['recreate_text'] = '<span>Recriar texto (Versão Premium)</span>';
        $actions['recreate_dalle'] = '<span>Recriar Imagem com DALLE (Versão Premium)</span>';
        $actions['recreate_mj']='<span>Recriar Imagem com Midjounal (Versão Premium)</span>';
    }
    return $actions;
}


function chatgpt_enqueue_admin_scripts($hook) {
    if ($hook !== 'edit.php') {
        return;
    }

    $path=get_site_url() . '/wp-content/plugins/chatgpt-post-creator/scripts/chatgpt-admin.js';



    wp_enqueue_script('chatgpt-admin', $path, array('jquery'), '1.0.0', true);

    $ajax_object = array(
        'ajax_nonce' => wp_create_nonce('chatgpt-ajax-nonce'),
        'ajax_url' => admin_url('admin-ajax.php')
    );

    wp_localize_script('chatgpt-admin', 'chatgpt_ajax_object', $ajax_object);
}
