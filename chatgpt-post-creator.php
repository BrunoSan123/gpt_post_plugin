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
    require_once(dirname(__FILE__).'/functions/handle_functions.php');
    require_once(dirname(__FILE__).'/functions/gpt_text_functions.php');
    require_once(dirname(__FILE__).'/functions/ia_image_generation_functions.php');
    require_once(dirname(__FILE__).'/functions/pages_functions.php');
    require_once(dirname(__FILE__).'/functions/pages.php');

    //require_once(dirname(__FILE__).'/crentials/credentials.php');
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
//primeira retirada
add_filter('handle_bulk_actions-edit-post', 'chatgpt_handle_bulk_action', 10, 3);







// Adicionar link "Recriar texto" na lista de ações em linha na tabela de postagens - (PREMIUM)
// segunda retirada
add_filter('post_row_actions', 'chatgpt_add_recreate_text_link', 10, 2);





// Adicionar scripts e estilos necessários para a página de administração (FREE)
//terceira retirada
add_action('admin_enqueue_scripts', 'chatgpt_enqueue_admin_scripts');






// Lidar com a chamada AJAX para recriar um único texto (PREMIUM)
// quarta retirada

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
//quinta retirada

//funções paga gerar imagem
//sexta retirada


//função que faz busca de imagem com a API Custom-Search


// Renderizar a página de opções do plugin com a interface para o usuario inserir chave API do chatGPT, o Prompt de sua Preferencia e as Palavras Chaves (FREE)
//setima retirada


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
//oitava retirada





// Adicionar página de opções do plugin ao menu de configurações (FREE)
//nona retirada
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


