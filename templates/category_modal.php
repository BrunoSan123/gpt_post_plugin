
<div class="modal_category">
    <div class="modal-child">
        <form action="" method="post">
            <input type="text" name="new_category" id="category_input_text">
            <input type="submit" value="Adicionar">
        </form>
    </div>
</div>
<?php
        if(isset($_POST['new_category'])){
            $category_name=$_POST['new_category'];
                create_new_category($category_name);
            }
?>