<?php


function is_author_user(){
    $current_user=wp_get_current_user();
    if(in_array('Admnistrator',$current_user->roles)){
        return true;
    }else{
        return print_r('Sem provolégios');
    }
}    

function create_new_user($user,$password,$email){
    if(is_author_user()){
        if(!username_exists($user) && !email_exists($email)){
            $user_id= wp_create_user($user,$password,$email);
            $user = new WP_User($user_id);
            $user->add_role('author');
        }
    }else{
        echo "Usuário ou endereço de email já existentes";
    }
}


function create_new_category($name){
    
         $result=wp_create_category($name);
    
        if(!is_wp_error($result)){
            echo 'categoria criada com sucesso';
        }else{
            echo 'falha em criar categoria';
        }
    }





