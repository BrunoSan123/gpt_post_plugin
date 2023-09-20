
<div class="modal_category">
    <div class="modal-child">
        <form action="" method="post">
            <div class="container_category">
                <input type="text" name="new_category" id="category_input_text">
                <input type="submit" value="Adicionar Categoria">
            </div>
            <div class="container_user">
                <input type="text" name="new_user" id="category_input_text">
                <input type="text" name="password" id="pass" class="user_input_password">
                <input type="email" name="mail" id="new_user_email">
                <input type="submit" value="Adicionar Usuario">
            </div>
        </form>
        <button class="btn_close">X</button>
    </div>
</div>
<?php
        if(isset($_POST['new_category'])){
            $category_name=$_POST['new_category'];
                create_new_category($category_name);
            }

        if(isset($_POST['new_user']) && isset($_POST['password'])){
            $user_name=$_POST['new_user'];
            $password_raw=$_POST['password'];
            $email=$_POST['mail'];
            $password=base64_encode($password_raw);
            create_new_user($user_name,$password,$email);
        }
?>