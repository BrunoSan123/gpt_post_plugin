<?php
function chatgpt_plugin_menu() {
        add_menu_page(
            __( 'Configurações do ChatGPT Autopost', 'textdomain'),
            'Auto Post',
            'manage_options',
            'chatgpt_plugin',
            'chatgpt_plugin_options_page',
            '',
            6
        ) ; 
        //add_options_page('Configurações do ChatGPT Autopost', 'ChatGPT Autopost', 'manage_options', 'chatgpt_plugin', 'chatgpt_plugin_options_page');
    }
    