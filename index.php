<?php
    session_start();
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    $send = false;
    $message = '';
    $newExtensionImage = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['csrf'])) {
            $message = 'Requête invalide (CSRF).';
        } elseif (!isset($_FILES['image'])) {
            $message = 'Aucun fichier reçu.';
        } else {
            $file = $_FILES['image'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors = [
                    UPLOAD_ERR_INI_SIZE   => "Fichier trop volumineux (ini).",
                    UPLOAD_ERR_FORM_SIZE  => "Fichier trop volumineux (form).",
                    UPLOAD_ERR_PARTIAL    => "Fichier partiellement transféré.",
                    UPLOAD_ERR_NO_FILE    => "Aucun fichier envoyé.",
                    UPLOAD_ERR_NO_TMP_DIR => "Dossier temporaire manquant.",
                    UPLOAD_ERR_CANT_WRITE => "Échec d'écriture sur le disque.",
                    UPLOAD_ERR_EXTENSION  => "Transfert interrompu par une extension.",
                ];
                $message = $errors[$file['error']] ?? ('Erreur d\'upload (code '.$file['error'].').');
            } elseif ($file['size'] > 3000000) {
                $message = 'Fichier trop volumineux (max 3 Mo).';
            } elseif (!is_uploaded_file($file['tmp_name'])) {
                $message = 'Fichier invalide.';
            } else {
                $mime = null;
                if (function_exists('finfo_open')) {
                    $fi = @finfo_open(FILEINFO_MIME_TYPE);
                    if ($fi) { $mime = @finfo_file($fi, $file['tmp_name']); finfo_close($fi); }
                }
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
                $ext = ($mime && isset($allowed[$mime])) ? $allowed[$mime] : null;
                $imgType = function_exists('exif_imagetype') ? @exif_imagetype($file['tmp_name']) : false;
                if ($imgType === false) { $gi = @getimagesize($file['tmp_name']); $imgType = $gi ? $gi[2] : false; }
                $mapType = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif'];
                if ($imgType && isset($mapType[$imgType])) { $ext = $ext ?: $mapType[$imgType]; }
                if (!$ext) {
                    $message = 'Type de fichier non autorisé (PNG, JPEG, GIF).';
                } else {
                    $dim = @getimagesize($file['tmp_name']);
                    if (!$dim) {
                        $message = 'Fichier image invalide.';
                    } elseif ($dim[0] > 6000 || $dim[1] > 6000) {
                        $message = 'Dimensions trop grandes (max 6000x6000).';
                    } else {
                        if (!is_dir('uploads')) { @mkdir('uploads', 0755, true); }
                        $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
                        $target   = 'uploads/' . $safeName;
                        $reEncoded = false;
                        if (function_exists('imagecreatefromjpeg')) {
                            switch ($ext) {
                                case 'jpg':
                                case 'jpeg':
                                    $src = @imagecreatefromjpeg($file['tmp_name']);
                                    if ($src) { $reEncoded = @imagejpeg($src, $target, 90); imagedestroy($src); }
                                    break;
                                case 'png':
                                    $src = @imagecreatefrompng($file['tmp_name']);
                                    if ($src) { imagesavealpha($src, true); $reEncoded = @imagepng($src, $target, 6); imagedestroy($src); }
                                    break;
                                case 'gif':
                                    $src = @imagecreatefromgif($file['tmp_name']);
                                    if ($src) { $reEncoded = @imagegif($src, $target); imagedestroy($src); }
                                    break;
                            }
                        }
                        if (!$reEncoded) {
                            if (!move_uploaded_file($file['tmp_name'], $target)) {
                                $message = 'Erreur lors de l\'enregistrement du fichier.';
                            } else { $send = true; $newExtensionImage = $safeName; }
                        } else { $send = true; $newExtensionImage = $safeName; }
                    }
                }
            }
        }
    }
    $__skip_legacy = true;
    // Ecrivez le code PHP ici

    // L'image est-elle bien envoyée et y a-t-il des erreurs ?

    if(!$__skip_legacy && isset($_FILES['image'])&& $_FILES['image']['error'] === 0){

       // La taille de l'image est-elle bien inférieure ou égale à 3mo ?
       if($_FILES['image']['size'] <= 3000000){
        //L'extension de l'image est-elle correcte ?
                $informationImage = pathinfo($_FILES['image']['name']); 
                $extensionImage   = $informationImage['extension'];
                $extensionArray   = ['png','jpg','gif','jpeg'];
                if(in_array($extensionImage,$extensionArray)){
                    $newExtensionImage = time().rand().rand().'.'.$extensionImage;
                    move_uploaded_file($_FILES['image']['tmp_name'],'uploads/'.$newExtensionImage);
                    $send= true;
                }           

       }


    }
?>

<html>
    <head>
        <meta charset="utf-8" />
        <link rel="stylesheet" href="css/default.css">
        <link rel="icon" type="image/png" href="images/favicon.png">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
        <title>ShareFiles - Hébergez gratuitement vos images et en illimité</title>
    </head>
    <body>

        <header>
            <a href="../">
                <span>ShareFiles</span>
            </a>
        </header>

        <section>
            <h1>
                <?php
                if(isset($send) && $send){
                    echo'<img src="uploads/'.$newExtensionImage.'"alt="ShareFiles" style="max-width: 75%">';
                
                }
                else{
                    echo' <i class="fas fa-paper-plane"></i>';

                }
                ?>

            </h1>

             <?php if(isset($send)&&$send){ ?>
                <h2>Fichier envoyé avec succès !</h2>
                <p>Retrouvez ci-dessous le liens vers votre fichier :</p>
                <input type="text" id="link" value="http://localhost/uploads/<?=$newExtensionImage?>"readonly>

               <?php } else{ ?>
                
                <form method="post" action="index.php" enctype="multipart/form-data">
                <p>
                    <input type="hidden" name="MAX_FILE_SIZE" value="3000000">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <label for="image">Sélectionnez votre fichier</label><br>
                    <input type="file" name="image" id="image" accept="image/png, image/jpeg, image/gif" required>
                </p>
                <p id="send">
                    <button type="submit">Envoyer <i class="fas fa-long-arrow-alt-right"></i></button>
                </p>
                <?php if (!empty($message)): ?>
                <p style="color:#b91c1c; margin-top:-6px;">&nbsp;<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </form>


             <?php } ?>
             
              


        </section>
        
    </body>
</html>
