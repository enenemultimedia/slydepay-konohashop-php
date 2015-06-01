<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        
            include_once './classes/DbLayer.class.php';
            $settings= parse_ini_file("config/local.ini");
            $db = new DbLayer($settings["username"], $settings["password"], $settings["host"], $settings["database"]);
            $allrows = $db->getAllItems();
        ?>

        <form action="checkout.php" method="post">
            <table>
            
                    <?php
                            foreach ($allrows as $row):
                    ?>
                    <tr>
                        <td width="130px"><input type="checkbox" name="orderItems[]" value="<?php echo $row['id'] ?>"/> <?php echo $row['name'] ?></td>
                    <td width="130px"> <?php echo $row['price'] ?></td>
                    <td width="130px"> <?php echo $row['comment'] ?></td>
                    </tr>	
                <?php endforeach; ?>
                <tr>
                    <td colspan="1"><input type="reset" value="Cancel"/></td>
                    <td colspan="2"><input type="submit" value="Checkout"/></td>
                </tr>
            </table>
        </form>	
    </body>
</html>
