<?php
/*
Plugin Name: ChatGPT Autopost
Description: Um plugin para criar e postar automaticamente textos gerados pelo ChatGPT através palavras-chave e prompts personalizados.
Version: 2.1.1
Author: <a href="https://visaopontocom.com">VPC Digital</a>
*/

//integração com Freemius

use Illuminate\Support\Facades\Response;

if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    //import de funções
    require_once(dirname(__FILE__).'/functions/actions_functions.php');
    require_once(dirname(__FILE__).'/crentials/credentials.php');
    require_once(dirname(__FILE__).'/helpers/helpers.php');

    function enqueue_script_style(){
        wp_enqueue_style( 'gpt_plugin_style', plugin_dir_url(__FILE__).'style.css' );
    }

    add_action('init','enqueue_script_style');

    if ( function_exists( 'chatgpt_freemius_integration' ) ) {
        chatgpt_freemius_integration()->set_basename( true, __FILE__ );
    } else {
        // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
        if ( ! function_exists( 'chatgpt_freemius_integration' ) ) {
            

    // Create a helper function for easy SDK access.
    function chatgpt_freemius_integration() {
        global $chatgpt_freemius_integration;

        if ( ! isset( $chatgpt_freemius_integration ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $chatgpt_freemius_integration = fs_dynamic_init( array(
                'id'                  => '12370',
                'slug'                => 'autopost-chatgpt-wordpress',
                'type'                => 'plugin',
                'public_key'          => 'pk_b17266a611535aa5e68c49c007401',
                'is_premium'          => true,
                // If your plugin is a serviceware, set this option to false.
                'has_premium_version' => true,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'is_org_compliant'    => false,
                'menu'                => array(
                    'slug'           => 'chatgpt_plugin',
                    'contact'        => false,
                    'support'        => false,
                ),
                // Set the SDK to work in a sandbox mode (for development & testing).
                // IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
                'secret_key'          => 'undefined',
            ) );
        }

        return $chatgpt_freemius_integration;
    }

    // Init Freemius.
    chatgpt_freemius_integration();
    // Signal that SDK was initiated.
    do_action( 'chatgpt_freemius_integration_loaded' );
}



//função para salvar o modelo do chatGPT a ser utilizado
add_action('wp_ajax_save_model', 'chatgpt_plugin_save_model');

function chatgpt_plugin_save_model() {
    // Verifique se o modelo foi passado
    if(isset($_POST['model'])) {
        $model = sanitize_text_field($_POST['model']);
        
        // Aqui você pode salvar o modelo no banco de dados WordPress
        // Por exemplo, usando a função update_option:
        update_option('chatgpt_plugin_chatgpt_model', $model);
        
        // Envie uma resposta de sucesso
        wp_send_json_success();
    } else {
        // Se o modelo não foi passado, envie uma resposta de erro
        wp_send_json_error('No model provided');
    }
    
    // Certifique-se de sempre morrer em funções AJAX, ou mais saída pode ser adicionada à resposta
    wp_die();
}

//acessando URL AJAX para o seu script JavaScript
function chatgpt_plugin_enqueue_scripts($hook) {
    // Você pode querer verificar aqui se você está na página correta antes de enfileirar o script
    // Se o script deve ser enfileirado em todas as páginas do admin, você pode remover essa verificação
    
    
    wp_enqueue_script('chatgpt-plugin-ajax', plugin_dir_url(__FILE__) . 'modelo-chatgpt.js', array('jquery'), '1.0', true);
    wp_localize_script('chatgpt-plugin-ajax', 'chatgpt_plugin_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
    wp_enqueue_script('gpt_ligt_n_dark',plugin_dir_url(__FILE__).'scripts/elements.js',array(),'1.0.0',true);
}
add_action('admin_enqueue_scripts', 'chatgpt_plugin_enqueue_scripts');


// Adicione uma nova opção à lista de ações em massa: (PREMIUM)
function chatgpt_add_bulk_action($bulk_actions) {
    if (chatgpt_freemius_integration()->can_use_premium_code()) {
        $bulk_actions['chatgpt_recreate_texts'] = 'Recriar Textos';
    } else {
        $bulk_actions['chatgpt_recreate_texts_disabled'] = 'Recriar Textos (Versão Premium)';
    }
    
    return $bulk_actions;
}

add_filter('bulk_actions-edit-post', 'chatgpt_add_bulk_action');



//função que registra o Javascript que gera o Post de acordo o Status: Publicado, Rascunho ou Agendar Post
function chatgpt_enqueue_scripts() {
    wp_enqueue_script('chatgpt-post-status', plugin_dir_url(__FILE__) . 'chatgpt-post-status.js', array('jquery'), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'chatgpt_enqueue_scripts');



// Função para processar a ação em massa de recriação de texto e adicione-a como uma ação (PREMIUM)
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

add_filter('handle_bulk_actions-edit-post', 'chatgpt_handle_bulk_action', 10, 3);







// Adicionar link "Recriar texto" na lista de ações em linha na tabela de postagens - (PREMIUM)
function chatgpt_add_recreate_text_link($actions, $post) {
    if ( chatgpt_freemius_integration()->can_use_premium_code() ) {
        $actions['recreate_text'] = '<a href="#" data-post-id="' . esc_attr($post->ID) . '" class="chatgpt-recreate-text" onclick="recreate_text(this)">Recriar texto</a><span class="chatgpt-loading" style="display:none;"><img src="https://gptautopost.com/wp-content/plugins/chatgpt-post-creator/load.gif" alt="Carregando..." /></span>';
    } else {
        $actions['recreate_text'] = '<span>Recriar texto (Versão Premium)</span>';
    }
    return $actions;
}
add_filter('post_row_actions', 'chatgpt_add_recreate_text_link', 10, 2);





// Adicionar scripts e estilos necessários para a página de administração (FREE)
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
add_action('admin_enqueue_scripts', 'chatgpt_enqueue_admin_scripts');






// Lidar com a chamada AJAX para recriar um único texto (PREMIUM)
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
    $complete_prompt = str_replace('{palavra-chave}', $post->post_title, $saved_prompt);

    // Recrie o texto usando a função que gera o texto com ChatGPT
    $api_key = get_option('chatgpt_api_key');
    $new_text = chatgpt_generate_text($api_key, $complete_prompt);

    // Atualize o post com o novo texto gerado
    $updated_post = array(
        'ID' => $post_id,
        'post_content' => $new_text
    );

    wp_update_post($updated_post);

    wp_send_json_success();
}

add_action('wp_ajax_chatgpt_recreate_text_ajax', 'chatgpt_recreate_text_ajax');


//Salvando o valor do PROMPT para mostra sempre que a pagina for recarregada
add_action('wp_ajax_save_chatgpt_prompt', 'save_chatgpt_prompt');
function save_chatgpt_prompt() {
    if (isset($_POST['prompt'])) {
        update_option('chatgpt_saved_prompt', sanitize_textarea_field($_POST['prompt']));
        echo 'Prompt salvo com sucesso.';
    } else {
        echo 'Falha ao salvar o prompt.';
    }
    wp_die();
}





// Função para gerar texto usando a API ChatGPT (FREE)
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
                    print_r($final_text);
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
    for($i=0;$i<=$rounds;$i++){
        $final_prompt='Tendo em vista esta estrutura:'.$text.' Gere a seção ['.$rounds.'] com tom explicativo, formal, com ao menos 250 palavras';
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
    }
    return $big_text;

}

function generate_image_with_dall_e($api,$prompt,$post_id){
        $dall_e_api_url ='https://api.openai.com/v1/images/generations';

        $request_data=array(
            'prompt'=>$prompt,
            'n'=>1,
            'size'=>'1024x1024'
        );
        $headers=array(
            'Content-type:application/json',
            'Authorization:Bearer '.$api,
        );
        $curl=curl_init();
        curl_setopt($curl, CURLOPT_URL,$dall_e_api_url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($request_data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);

        if ($response === false) {
            $error_msg = curl_error($curl);
            curl_close($curl);
            return 'Error: ' . $error_msg;
        }

        curl_close($curl);
        $response_data = json_decode($response, true);
        //print_r($response_data);
        
        if (isset($response_data['data'])) {
            echo '<img src="'.$response_data['data'][0]['url'].'"/>';
    /*         $matches=array();
            preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $body, $matches);
            print_r($body); */
            importar_imagem_destaque($response_data['data'][0]['url'],$post_id,$prompt);
        } else {
            // Lidar com a falta da URL da imagem ou outros erros da API
            return print_r('Não foi por algum motivo');
        }
}

function generate_image_with_mj($mj_api, $prompt,$post_id){
    $image_array=[];
    $mj_url='https://api.thenextleg.io/v2/imagine';
    $request_data=array(
        'msg'=>$prompt,
        'ref'=> "",
        'webhookOverride'=> "",
        'ignorePrefilter'=> "false"
    );
    $headers=array(
        'Content-type:application/json',
        'Authorization:Bearer '.$mj_api,
    );

    $curl= curl_init();
    curl_setopt($curl, CURLOPT_URL,$mj_url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($request_data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($curl);
    curl_close($curl);

    $response_data=json_decode($response,true);

    $mj_image=get_MJ_img($response_data['messageId'],$mj_api,$post_id,$prompt,0,$image_array);
    print_r($mj_image);
    importar_imagem_destaque($mj_image,$post_id,$prompt);


}

//função que faz busca de imagem com a API Custom-Search

function search_image_with_google($prompt,$api_key,$search_id,$post_id){
    $google_url='https://www.googleapis.com/customsearch/v1?q='.$prompt.'&key='.$api_key.'&cx='.$search_id.'&searchType=image&rights=cc_attribute';
    $curl =curl_init($google_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Retorna a resposta como uma string
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Desativa a verificação SSL (não recomendado para produção)
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        echo 'Erro cURL: ' . curl_error($curl);
    }
    
    // Fecha a conexão cURL
    curl_close($curl);
    $response_data =json_decode($response);
    //print_r(var_dump($response_data->items[0]->link));
    if(isset($response_data->items)){
        echo '<img id="img_test" src="'.$response_data->items[0]->link.'"/>';
        importar_imagem_destaque($response_data->items[0]->link,$post_id,$prompt);
    }else{
        return print_r('Erro na imagem');
    }
}

function upload_image($post_id){
    $file=$_FILES['image_upload'];
    $name=$file['name'];
    $tmp_name = $file['tmp_name'];
    $wp_main_dir = ABSPATH;
    $temp_path = $wp_main_dir.'wp-content/uploads/'. $name;
    print_r($temp_path);
    move_uploaded_file($tmp_name,$temp_path);
    $filetype = wp_check_filetype( $temp_path, null );
    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name( $name ),
        'post_content'   => '',
        'post_status'    => 'inherit',
    );
    $attachment_id = wp_insert_attachment( $attachment, $temp_path );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    $attach_data = wp_generate_attachment_metadata( $attachment_id, $temp_path );
    wp_update_attachment_metadata( $attachment_id, $attach_data );
    set_post_thumbnail( $post_id, $attachment_id );
    return 'Imagem inserida na biblioteca e definida como imagem de destaque com o ID: ' . $attachment_id;

}

function importar_imagem_destaque($imagem_url, $post_id,$image_name) {
    // Certifique-se de que o WordPress está carregado
    if ( ! defined( 'ABSPATH' ) ) {
        require_once( 'wp-load.php' );
    }

    // Faz a requisição segura para obter o conteúdo da imagem
    $response = wp_safe_remote_get( $imagem_url );
    print_r($response);

    // Verifica se a requisição foi bem-sucedida
    if ( is_wp_error( $response ) ) {
        // Lida com o erro, se necessário
        return 'Erro ao buscar a imagem: ' . esc_html( $response->get_error_message() );
    } else {
        // Obtém o conteúdo da resposta
        $body = wp_remote_retrieve_body( $response );

        // Gere um nome de arquivo para a imagem (você pode personalizá-lo conforme necessário)
        $filename = $image_name.'.jpg';

        // Caminho completo para onde a imagem será salva temporariamente
        $temp_path = WP_CONTENT_DIR . '/uploads/' . $filename;

        // Salva o conteúdo da imagem em um arquivo temporário
        file_put_contents( $temp_path, $body );

        // Configuração do tipo de mídia a ser inserido na biblioteca
        $filetype = wp_check_filetype( $temp_path, null );

        // Array de dados do arquivo a ser inserido na biblioteca
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name( $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        // Faz o upload do arquivo para a biblioteca de mídia
        $attachment_id = wp_insert_attachment( $attachment, $temp_path );

        // Atualiza metadados do arquivo
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $attach_data = wp_generate_attachment_metadata( $attachment_id, $temp_path );
        wp_update_attachment_metadata( $attachment_id, $attach_data );

        // Define a imagem como imagem de destaque do post
        set_post_thumbnail( $post_id, $attachment_id );

        // Opcional: Exibe o ID do anexo inserido
        return 'Imagem inserida na biblioteca e definida como imagem de destaque com o ID: ' . $attachment_id;
    }
}


function get_MJ_img($msg,$api,$post_id,$prompt, $retryCount,$array){
    $maxRetry = 40;
    if(isset($msg)){
        $curl_get_url='https://api.thenextleg.io/v2/message/'.$msg.'?expireMins=2';
        $get_curl_mj=curl_init();
        $headers=array(
            'Content-type:application/json',
            'Authorization:Bearer '.$api,
        );
        curl_setopt($get_curl_mj, CURLOPT_URL, $curl_get_url);
        curl_setopt($get_curl_mj, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($get_curl_mj, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($get_curl_mj, CURLOPT_TIMEOUT, 10);
        $get_respose=curl_exec($get_curl_mj);
        $get_response_data=json_decode($get_respose);
        curl_close($get_curl_mj);
        print_r($get_response_data->progress);
        
        if($get_response_data->progress===100){
            if(isset($get_response_data->response->imageUrls[0])){
                $array[]=$get_response_data->response->imageUrls[0];
                return $array[0];
            }else{
                print_r('MidJourney falahou em obter a imagem');
            }
        }
        if($get_response_data->progress==='incomplete'){
            throw new Exception('Midjourney Image generation failed');
        }
        if ($retryCount > $maxRetry) {
            throw new Exception('Max retries exceeded');
        }

    }else{
        echo 'Erro no post'; 
    }
    sleepMilliseconds(1000);
    return get_MJ_img($msg,$api,$post_id,$prompt,$retryCount+1,$array);
}




// Renderizar a página de opções do plugin com a interface para o usuario inserir chave API do chatGPT, o Prompt de sua Preferencia e as Palavras Chaves (FREE)
function chatgpt_plugin_options_page() {
    
    // Verificar se os dados do formulário foram enviados
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Atualizar a opção com o modelo selecionado
        $selected_model=isset($_POST['chatgpt_model'])?sanitize_text_field($_POST['chatgpt_model']):'';
        update_option('chatgpt_selected_model', $selected_model);
        
    }
    
    
    $api_key = get_option('chatgpt_api_key');
    $google_api_key =get_option('google_search_key');
    $google_search_id =get_option('google_search_id');
    $saved_prompt = get_option('chatgpt_saved_prompt');
    $selected_model = get_option('chatgpt_selected_model');
     $categories = get_categories(array('hide_empty' => 0));
     $args = array(
        'role__in' => array('administrator', 'editor', 'author', 'contributor'),
        'orderby' => 'display_name',
        'order' => 'ASC',
    );
    $authors = get_users($args);
   
   
   // Lista de modelos de ChatGPT disponíveis
    $models = array(
        'gpt-3.5-turbo-16k' => 'GPT-3.5',
        'gpt-4' => 'GPT-4',
    );

    //icons for gtp_switch
    $icons=array(
        '<svg class="gpt_dash_icos" data-name="ray" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke-width="2" class="h-4 w-4 transition-colors text-brand-green"><path fill="currentColor" d="M9.586 1.526A.6.6 0 0 0 8.553 1l-6.8 7.6a.6.6 0 0 0 .447 1h5.258l-1.044 4.874A.6.6 0 0 0 7.447 15l6.8-7.6a.6.6 0 0 0-.447-1H8.542l1.044-4.874Z"/></svg>',
        '<svg class="gpt_dash_icos" data-name="stars" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke-width="2" class="h-4 w-4 transition-colors group-hover/button:text-brand-purple"><path fill="currentColor" d="M12.784 1.442a.8.8 0 0 0-1.569 0l-.191.953a.8.8 0 0 1-.628.628l-.953.19a.8.8 0 0 0 0 1.57l.953.19a.8.8 0 0 1 .628.629l.19.953a.8.8 0 0 0 1.57 0l.19-.953a.8.8 0 0 1 .629-.628l.953-.19a.8.8 0 0 0 0-1.57l-.953-.19a.8.8 0 0 1-.628-.629l-.19-.953h-.002ZM5.559 4.546a.8.8 0 0 0-1.519 0l-.546 1.64a.8.8 0 0 1-.507.507l-1.64.546a.8.8 0 0 0 0 1.519l1.64.547a.8.8 0 0 1 .507.505l.546 1.641a.8.8 0 0 0 1.519 0l.546-1.64a.8.8 0 0 1 .506-.507l1.641-.546a.8.8 0 0 0 0-1.519l-1.64-.546a.8.8 0 0 1-.507-.506L5.56 4.546Zm5.6 6.4a.8.8 0 0 0-1.519 0l-.147.44a.8.8 0 0 1-.505.507l-.441.146a.8.8 0 0 0 0 1.519l.44.146a.8.8 0 0 1 .507.506l.146.441a.8.8 0 0 0 1.519 0l.147-.44a.8.8 0 0 1 .506-.507l.44-.146a.8.8 0 0 0 0-1.519l-.44-.147a.8.8 0 0 1-.507-.505l-.146-.441Z"/></svg>'
    )
    
     
    ?>
    <script>
        function enableApiKeyEditing() {
            var apiKeyInput = document.getElementById('chatgpt_api_key');
            var googleKeys=document.querySelectorAll('.google_api_fields')
            apiKeyInput.readOnly = !apiKeyInput.readOnly;
            googleKeys.forEach((e)=>{
                e.readOnly=!e.readOnly;
            })
        }
    </script>
    
    <div class="wrap">
        <?php require_once(plugin_dir_path(__FILE__).'templates/gpt_mode_swicth.php')?>
        <h2 class="autopost_title">Autopost Plus</h2>
        <div class="form_parent">
       
        <form action="" method="post" class="main_form" enctype="multipart/form-data">
           <?php require_once(plugin_dir_path(__FILE__).'templates/sidebar_plugin.php');?>
            <div class="form_item">
            <?php settings_fields('chatgpt_plugin_options'); ?>
            <?php do_settings_sections('chatgpt_plugin'); ?>

            <p class="api_config" style="text-align: center;"><strong>Configurações de chave API</strong></p>

            <div class="chat_gpt_vonfiguration">
            <p>Caso não tenha uma chave API, gere uma no link: <a href="https://platform.openai.com/account/api-keys" target="_blank">https://platform.openai.com/account/api-keys</a></p>
            <input type="text" id="chatgpt_api_key" name="chatgpt_api_key" value="<?php echo esc_attr($api_key); ?>" readonly />
            <input type="submit" class="button" name="save_api_key" value="Salvar ChaveAPI" />
            <input type="button" class="button" value="Editar Chave API" onclick="enableApiKeyEditing()" />
            <code style="font-size:12px;font-style: italic;">o GPT-3 da OpenAI tem limitações quanto ao número de tokens por resposta, o que pode limitar a extensão dos textos gerados. A versão da Vinci do GPT-3 pode lidar com até 2048 tokens por request, o que em muitos casos pode ser menos do que 2000 palavras, dependendo do idioma e da complexidade do texto.</code>
            <br><br>
            <code>Configure aqui suas credenciais do google para a busa inteligente</code>

            <input type="text" id="google_search_key" class="google_api_fields" name="google_api_key" value="<?php echo esc_attr($google_api_key); ?>" readonly />
            <input type="text" id="google_search_id"  class="google_api_fields" name="google_search_id" value="<?php echo esc_attr($google_search_id); ?>" readonly />
            </div>

    <div class="gpt_input_generation">
        <div class="generation_parent">
         <div class="generation_child">
            <p class="inputs_title"><strong>PROMPT</strong></p>
            <textarea class="chat_textarea" id="chatgpt_prompt" name="chatgpt_prompt" rows="5" cols="100" placeholder="Escreva um artigo sobre {palavra-chave}"><?php echo esc_textarea($saved_prompt); ?></textarea>
            <div class="generate_text_button">
            <input style="margin-top: 3%; text-decoration:none; color:#fff;" type="button" class="button btn_submit_text" id="save_prompt" value="Salvar Prompt" <?php echo chatgpt_freemius_integration()->is_not_paying() ? 'disabled' : ''; ?> />
            </div>
        </div>

        <?php if ( chatgpt_freemius_integration()->is_not_paying() ) : ?>
            <span>(Versão Premium)</span>
        <?php endif; ?>

           
            <div class="generation_child">
                <p class="inputs_title"><strong>PALAVAS CHAVE</strong></p>
                <textarea class="chatgpt_keys chat_textarea" name="chatgpt_keywords" rows="10" cols="100" placeholder="Palavra-chave 1&#10;Palavra-chave 2&#10;Palavra-chave 3"></textarea>
            </div>
            
            <br><br>
         


        <?php if ( chatgpt_freemius_integration()->is_not_paying() ) : ?>
            <span>(Versão Premium)</span>
        <?php endif; ?>

            <br><br>

        <?php if ( chatgpt_freemius_integration()->is_not_paying() ) : ?>
            <span>(Versão Premium)</span>
        <?php endif; ?>


<input type="hidden" name="chatgpt_form_submitted" value="1">

<?php if ( chatgpt_freemius_integration()->is_not_paying() ) : ?>
    <span>(Versão Premium)</span>
<?php endif; ?>


            <div class="generate_text_button">
                <input name="submit" class="button btn_submit_text button-primary" type="submit" value="<?php esc_attr_e('Gerar Textos'); ?>" />
           </div>
        </div>
    </div>
        </div>
        </form>
        </div>
    </div>
    <?php require_once(dirname(__FILE__).'/templates/category_modal.php');?>
    <?php
    
}


//função para registrar as configurações do plugin, incluindo a chave API (FREE)
function chatgpt_register_prompt_settings() {
    register_setting('chatgpt-settings-group', 'chatgpt_saved_prompt');
}
add_action('admin_init', 'chatgpt_register_prompt_settings');


add_action('admin_init', 'chatgpt_register_settings');


//Registra a opção chatgpt_api_key ao ativar o plugin (Liberar na versão FREE)
function chatgpt_initialize_api_key_option() {
    add_option('chatgpt_api_key', '');
}
register_activation_hook(__FILE__, 'chatgpt_initialize_api_key_option');




//função que chama o arquivo chatgpt-post-status.js
function chatgpt_post_status_enqueue_scripts($hook) {
    // Verifique se está na página de opções do plugin
    if ($hook != 'settings_page_chatgpt_plugin') {
        return;
    }

    // Registre e adicione o arquivo JavaScript
    wp_register_script('chatgpt_post_status', plugin_dir_url(__FILE__) . 'chatgpt-post-status.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('chatgpt_post_status');
}

//Vincula a açao da chamada do Javascript chatgpt-post-status.js
add_action('admin_enqueue_scripts', 'chatgpt_post_status_enqueue_scripts');



// Gerar e publicar postagens com base no prompt e palavras-chave fornecidas
function chatgpt_generate_and_publish_posts() {
    if (!current_user_can('manage_options') || !isset($_POST['chatgpt_form_submitted'])) {
        return;
    }

    if (isset($_POST['submit'])) {
        
    

        
        $prompt = isset($_POST['chatgpt_prompt']) ? sanitize_text_field($_POST['chatgpt_prompt']) : '';
        $keywords_string = isset($_POST['chatgpt_keywords']) ? sanitize_textarea_field($_POST['chatgpt_keywords']) : '';
        $keywords = explode("\n", $keywords_string);
        $api_key = get_option('chatgpt_api_key');
        $google_api_key=get_option('google_search_key');
        $google_search_id=get_option('google_search_id');
        $schedule_datetime = isset($_POST['schedule_datetime']) ? sanitize_text_field($_POST['schedule_datetime']) : '';
        $selected_category_id = intval($_POST['chatgpt_category']);
        $selected_author_id = intval($_POST['chatgpt_author']);

        foreach ($keywords as $keyword) {
            try {
                $keyword = trim($keyword);
                $complete_prompt = str_replace('{palavra-chave}', $keyword, $prompt);
                $generated_text = chatgpt_generate_text($api_key, $keyword);
                
                

                if ($generated_text === null || $generated_text === '') {
                    throw new Exception('Error: Generated text is empty or null.');
                }
  

                $post_data = array(
                    'post_title'    => $keyword,
                    'post_content'  => $generated_text,
                    'post_status'   => 'publish',
                    'post_author' => $selected_author_id,
                    'post_category' => array($selected_category_id),
                    'post_type'     => 'post',
                );

                $post_status_option = $_POST['post_status'];
                if ($post_status_option === 'schedule') {
                    $post_data['post_status'] = 'future';
                    $post_data['post_date'] = date('Y-m-d H:i:s', strtotime($_POST['schedule_datetime']));
                } elseif ($post_status_option === 'draft') {
                    $post_data['post_status'] = 'draft';
                }

                if ($post_status_option === 'schedule' && !empty($schedule_datetime)) {
                    $post_data['post_date'] = $schedule_datetime;
                    $post_data['post_date_gmt'] = get_gmt_from_date($schedule_datetime);
                }



                $post_id = wp_insert_post($post_data);
                
                if(isset($_POST['ia_send'])){
                    upload_image($post_id);
                 }
                
                // geração de imagem com o DALL-E se selecionada a opção
                if(isset($_POST['ia_dalle'])){
                    $generated_image =generate_image_with_dall_e($api_key,$keyword,$post_id);
                    if($generated_image===null || $generated_image===''){
                        throw new Exception('Error: Generated Image is empty or null');
                    }
                    
                }

                if(isset($_POST['ia_midjournal'])){
                    $token=Credentials::getCredentials();
                    
                    $mj_generated_image=generate_image_with_mj($token,$keyword,$post_id);
                    if($mj_generated_image===null || $mj_generated_image===''){
                        throw new Exception('Error: Generated Image is empty or null');
                    }
                }

                // buscca de imagens com a api do customSearch do google
                if(isset($_POST['ia_google_image'])){
                    $imagem= search_image_with_google($keyword,$google_api_key, $google_search_id,$post_id);
                    if($imagem===null || $imagem===''){
                        throw new Exception('Error: Generated Image is empty or null');
                    }
                    set_post_thumbnail($post_id, $imagem);
                }


                
            } catch (Exception $e) {
                // Log the error message for debugging
                error_log($e->getMessage());

                // Return a friendly error message to the user
                return 'An error occurred while generating the text. Please try again. If the problem persists, contact the site administrator.';
            }
        }

        chatgpt_show_success_message();
    }
}





// Adicionar página de opções do plugin ao menu de configurações (FREE)
function chatgpt_plugin_menu() {
/*     add_menu_page(
        __( 'Configurações do ChatGPT Autopost', 'textdomain'),
        'ChatGPT Autopost',
        'manage_options',
        'chatgpt_plugin',
        'chatgpt_plugin_options_page',
        '',
        6
    ) ; */
    add_options_page('Configurações do ChatGPT Autopost', 'ChatGPT Autopost', 'manage_options', 'chatgpt_plugin', 'chatgpt_plugin_options_page');
}

add_action('admin_menu', 'chatgpt_plugin_menu');






// gerar e publicar postagens conforme necessário (FREE)
function chatgpt_check_submit_and_generate() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Verificar se o botão "Salvar ChaveAPI" foi clicado e salvar a chave API
    if (isset($_POST['save_api_key'])) {
        $api_key = isset($_POST['chatgpt_api_key']) ? sanitize_text_field($_POST['chatgpt_api_key']) : '';
        $google_api_key=isset($_POST['google_api_key'])? sanitize_text_field($_POST['google_api_key']):'';
        $google_search_api_id=isset($_POST['google_search_id'])?sanitize_text_field($_POST['google_search_id']):'';
        update_option('chatgpt_api_key', $api_key);
        update_option('google_search_key',$google_api_key);
        update_option('google_search_id',$google_search_api_id);
    }


    // Verificar se o botão "Criar Textos" foi clicado
    if (isset($_POST['submit']) && (!isset($_POST['chatgpt_form_type']) || $_POST['chatgpt_form_type'] !== 'save_prompt')) {
        chatgpt_generate_and_publish_posts();
    }

    // Verificar se o botão "Salvar Prompt" foi clicado
    if (isset($_POST['submit']) && isset($_POST['chatgpt_form_type']) && $_POST['chatgpt_form_type'] === 'save_prompt') {
        chatgpt_save_prompt();
    }
}

add_action('admin_init', 'chatgpt_check_submit_and_generate');




// Função para adicionar uma página de opções ao menu do plugin (PREMIUM)
function chatgpt_create_settings_menu() {
    if (!chatgpt_freemius_integration()->is_premium()) {
        return;
    }

    add_options_page(
        'ChatGPT Settings',
         'manage_options',
        'chatgpt-settings',
        'chatgpt_settings_page'
    );
}
add_action('admin_menu', 'chatgpt_create_settings_menu');








//Registrando a opção chatgpt_saved_prompt e associá-la à nossa página de configurações (PREMIUM)
function chatgpt_register_settings() {
    if (!chatgpt_freemius_integration()->is_premium()) {
        return;
    } {
    register_setting('chatgpt-settings-group', 'chatgpt_saved_prompt');
}
}
add_action('admin_init', 'chatgpt_register_settings');



// Salvar o modelo de prompt padrão para recriação automática dos textos (PREMIUM)
function chatgpt_save_prompt() {
    if (!chatgpt_freemius_integration()->can_use_premium_code()) {
        echo 'Esta funcionalidade está disponível apenas para usuários premium.';
        return;
    }

    if (isset($_POST['chatgpt_saved_prompt'])) {
        update_option('chatgpt_saved_prompt', sanitize_text_field($_POST['chatgpt_saved_prompt']));
        update_option('chatgpt_prompt_saved', 1);
    }
}



// Exibir notificação para informar o usuário se os textos em massa foram recriados com sucesso 
function chatgpt_bulk_action_notice() {
   {
    if (!empty($_REQUEST['chatgpt_recreated_texts'])) {
        $recreated_texts_count = intval($_REQUEST['chatgpt_recreated_texts']);
        printf(
            '<div id="message" class="updated notice is-dismissible"><p>' .
            _n(
                '%s texto recriado com sucesso.',
                '%s textos recriados com sucesso.',
                $recreated_texts_count
            ) .
            '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>',
            $recreated_texts_count
        );
    }
}
}
add_action('admin_notices', 'chatgpt_bulk_action_notice');
}



// Mostrar mensagem de sucesso quando os textos são criados e publicados com sucesso (FREE)
function chatgpt_show_success_message() {
    add_settings_error('chatgpt_messages', 'chatgpt_message', 'Textos criados e publicados com sucesso!', 'updated');
}


