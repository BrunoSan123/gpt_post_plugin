<?php

function chatgpt_recreate_text_ajax() {
    if (!chatgpt_freemius_integration()->can_use_premium_code()) {
        wp_send_json_error(array('message' => 'Acesso negado. Esta funcionalidade é apenas para usuários premium.'));
        return;
    }

    check_ajax_referer('chatgpt-ajax-nonce', 'security');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Você não tem permissão para editar posts.'));
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if ($post_id <= 0) {
        wp_send_json_error(array('message' => 'ID de post inválido.'));
    }

    $post = get_post($post_id);

    if (!$post) {
        wp_send_json_error(array('message' => 'Post não encontrado.'));
    }

    // Recupere o prompt salvo e substitua {palavra-chave} pelo título do post
    $saved_prompt = get_option('chatgpt_saved_prompt', 'Escreva um artigo sobre {palavra-chave}');
    //$complete_prompt = str_replace('{palavra-chave}', $post->post_title, $saved_prompt);

    // Recrie o texto usando a função que gera o texto com ChatGPT
    $api_key = get_option('chatgpt_api_key');
    $new_text = chatgpt_generate_text($api_key, $post->post_title);

    // Atualize o post com o novo texto gerado
    $updated_post = array(
        'ID' => $post_id,
        'post_content' => $new_text
    );

    wp_update_post($updated_post);

    wp_send_json_success();
}

function dalle_recreate_image_ajax(){
    if (!chatgpt_freemius_integration()->can_use_premium_code()) {
        wp_send_json_error(array('message' => 'Acesso negado. Esta funcionalidade é apenas para usuários premium.'));
        return;
    }

    check_ajax_referer('chatgpt-ajax-nonce', 'security');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Você não tem permissão para editar posts.'));
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if ($post_id <= 0) {
        wp_send_json_error(array('message' => 'ID de post inválido.'));
    }

    $post = get_post($post_id);

    if (!$post) {
        wp_send_json_error(array('message' => 'Post não encontrado.'));
    }

    $api_key = get_option('chatgpt_api_key');

    generate_image_with_dall_e($api_key,$post->title,$post_id);

    wp_send_json_success();



}


function chatgpt_generate_text($api_key,$key) {

    $prompt='Crie uma outline com ao menos 10 seções sobre'.$key.', considere uma estrutura de reviews';
    $selected_model = get_option('chatgpt_selected_model');
    if (!$selected_model) {
        $selected_model = 'gpt-3.5-turbo-16k';
    }

    if (in_array($selected_model, ['gpt-3.5-turbo-16k', 'gpt-4-32k', 'gpt-4'])) {
        $url = 'https://api.openai.com/v1/chat/completions';
        $body = array(
            'model' => $selected_model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.7,
            'max_tokens' => 3950, // Reduzindo um pouco para ter uma margem de segurança
        );
    } else {
        print_r($selected_model);
    }

    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($curl);

    if ($response === false) {
        $error_msg = curl_error($curl);
        curl_close($curl);
        return 'Error: ' . $error_msg;
    }

    curl_close($curl);
    $json_response = json_decode($response, true);

    if (isset($json_response['error'])) {
        return 'Error: ' . $json_response['error']['message'];
    } else {
        $generated_text = '';
         if (isset($json_response['choices'])) {
            $messages = $json_response['choices'];
            foreach ($messages as $message) {
                if ($message['message']['role'] == 'assistant') {
                    $generated_text = $message['message']['content'];
                    $final_text=generate_big_text($generated_text,$selected_model,$api_key,6);
                    //print_r($final_text);
                }

            }
        } else {
            return 'Error: Unexpected response format.';
        }
        return $final_text;
    }
}


function generate_big_text($text,$model,$api_key,$rounds){
    $big_text='';
    $final_prompt='Tendo em vista esta estrutura:'.$text.' Gere a seção [1] com tom explicativo, formal, com ao menos 250 palavras';
        $final_body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $final_prompt
                )
            ),
            'temperature' => 0.7,
            'max_tokens' => 3950, // Reduzindo um pouco para ter uma margem de segurança
        );
        $second_headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        );
        $url_second = 'https://api.openai.com/v1/chat/completions';
        $curl_final_text = curl_init();
        curl_setopt($curl_final_text, CURLOPT_URL, $url_second);
        curl_setopt($curl_final_text, CURLOPT_POST, 1);
        curl_setopt($curl_final_text, CURLOPT_POSTFIELDS, json_encode($final_body));
        curl_setopt($curl_final_text, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_final_text, CURLOPT_HTTPHEADER, $second_headers);

        $response_new = curl_exec($curl_final_text);
        //print_r($response_new);

        if ($response_new === false) {
            $error_msg = curl_error($curl_final_text);
            curl_close($curl_final_text);
            return 'Error: ' . $error_msg;
        }
        curl_close($curl_final_text);
        $json_final_response = json_decode($response_new, true);
        if (isset($json_final_response['error'])) {
            return 'Error: ' . $json_final_response['error']['message'];
        }else{
            $real_generated_text=$json_final_response['choices'][0]['message']['content'];
            $big_text.=$real_generated_text;
            
        }
    return $big_text;

}


