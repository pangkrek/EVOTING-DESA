   <?php
   $password = 'password'; // Ganti dengan password yang diinginkan
   $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
   echo $hashedPassword; // Salin hasil hash ini
   ?>
   