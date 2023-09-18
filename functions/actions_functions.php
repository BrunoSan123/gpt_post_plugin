<?php


    function create_new_category($name){
        //category slug
        $category_slug='_'.$name;
        //the taxonomy for a given category
/*         $taxonomy='category';
       
        $args=array(
            'description'=>'user'.$name,
            'slug'=>$category_slug,
            'parent'=>0
        ); */
    
        $result=wp_create_category($name);
    
        if(!is_wp_error($result)){
            echo 'categoria criada com sucesso';
        }else{
            echo 'falha em criar categoria';
        }
    }



