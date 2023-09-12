<head>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;1,100;1,300&display=swap" rel="stylesheet">
</head>
<?php
/*
Plugin Name: ChatGPT Autopost
Description: Um plugin para criar e postar automaticamente textos gerados pelo ChatGPT através palavras-chave e prompts personalizados.
Version: 2.1.1
Author: <a href="https://visaopontocom.com">VPC Digital</a>
*/

//integração com Freemius
if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

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
function chatgpt_generate_text($api_key, $prompt) {

    $selected_model = get_option('chatgpt_selected_model');
    if (!$selected_model) {
        $selected_model = 'text-davinci-003';
    }

    if (in_array($selected_model, ['gpt-3.5-turbo', 'gpt-4-32k', 'gpt-4'])) {
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
        $url = 'https://api.openai.com/v1/engines/'.$selected_model.'/completions';
        $body = array(
            'prompt' => $prompt,
            'temperature' => 0.7,
            'max_tokens' => 3950, // Reduzindo um pouco para ter uma margem de segurança
        );
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
            $generated_text = $json_response['choices'][0]['text'];
        } else if (isset($json_response['messages'])) {
            $messages = $json_response['messages'];
            foreach ($messages as $message) {
                if ($message['role'] == 'assistant') {
                    $generated_text = $message['content'];
                }
            }
        } else {
            return 'Error: Unexpected response format.';
        }
        return $generated_text;
    }
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
        'text-davinci-003' => 'GPT-3.5',
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
            apiKeyInput.readOnly = !apiKeyInput.readOnly;
        }
    </script>
    
    <div class="wrap">
        <?php require_once(plugin_dir_path(__FILE__).'templates/gpt_mode_swicth.php')?>
        <h2 class="autopost_title">Autopost Plus</h2>
        <div class="form_parent">
       
        <form action="" method="post" class="main_form">
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
        $schedule_datetime = isset($_POST['schedule_datetime']) ? sanitize_text_field($_POST['schedule_datetime']) : '';
        $selected_category_id = intval($_POST['chatgpt_category']);
        $selected_author_id = intval($_POST['chatgpt_author']);

        foreach ($keywords as $keyword) {
            try {
                $keyword = trim($keyword);
                $complete_prompt = str_replace('{palavra-chave}', $keyword, $prompt);
                $generated_text = chatgpt_generate_text($api_key, $complete_prompt);

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
        update_option('chatgpt_api_key', $api_key);
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


