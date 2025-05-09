<?php
$pdo = new PDO('sqlite:./database/identifier.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- SUPPRESSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];

    $stmtImg = $pdo->prepare("SELECT image FROM utilisateur WHERE id = ?");
    $stmtImg->execute([$deleteId]);
    $user = $stmtImg->fetch();
    if ($user && $user['image'] && file_exists($user['image'])) {
        unlink($user['image']);
    }

    $pdo->prepare("DELETE FROM utilisateur WHERE id = ?")->execute([$deleteId]);
    header('Location: index.php');
    exit;
}

// --- MISE À JOUR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $editId = (int) $_POST['edit_id'];
    $nom = trim(htmlspecialchars($_POST['nom']));
    $prenom = trim(htmlspecialchars($_POST['prenom']));
    $email = trim($_POST['email']);
    $commentaire = trim($_POST['commentaire']);

    $imagePath = $_POST['current_image']; // image par défaut
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $filename = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            if ($imagePath && file_exists($imagePath)) unlink($imagePath);
            $imagePath = $targetFile;
        }
    }

    $stmt = $pdo->prepare("UPDATE utilisateur SET nom=?, prenom=?, email=?, commentaire=?, image=? WHERE id=?");
    $stmt->execute([$nom, $prenom, $email, $commentaire, $imagePath, $editId]);
    header('Location: index.php');
    exit;
}

// --- AJOUT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $nom = trim(htmlspecialchars($_POST['nom']));
    $prenom = trim(htmlspecialchars($_POST['prenom']));
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $commentaire = trim($_POST['commentaire']);

    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $filename = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, image, commentaire) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nom, $prenom, $email, $password, $imagePath, $commentaire]);
    header('Location: index.php');
    exit;
}

// --- UTILISATEURS ---
$utilisateurs = $pdo->query("SELECT * FROM utilisateur ORDER BY date_creation DESC")->fetchAll();

// --- MODIFICATION (pré-remplissage) ---
$editUser = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE id = ?");
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Utilisateurs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.15/dist/full.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-4xl mx-auto space-y-10">

    <!-- FORMULAIRE -->
    <div class="bg-white p-6 rounded-2xl shadow">
        <h2 class="text-2xl font-bold mb-4"><?= $editUser ? "Modifier l'utilisateur" : "Ajouter un utilisateur" ?></h2>
        <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input class="input input-bordered" type="text" name="prenom" placeholder="Prénom" required value="<?= $editUser['prenom'] ?? '' ?>">
            <input class="input input-bordered" type="text" name="nom" placeholder="Nom" required value="<?= $editUser['nom'] ?? '' ?>">
            <input class="input input-bordered" type="email" name="email" placeholder="Email" required value="<?= $editUser['email'] ?? '' ?>">
            <?php if (!$editUser): ?>
                <input class="input input-bordered" type="password" name="password" placeholder="Mot de passe" required>
            <?php endif; ?>
            <input class="file-input file-input-bordered" type="file" name="image">
            <textarea class="textarea textarea-bordered md:col-span-2" name="commentaire" placeholder="Commentaire"><?= $editUser['commentaire'] ?? '' ?></textarea>

            <?php if ($editUser): ?>
                <input type="hidden" name="edit_id" value="<?= $editUser['id'] ?>">
                <input type="hidden" name="current_image" value="<?= $editUser['image'] ?>">
                <button type="submit" class="btn btn-warning md:col-span-2">Mettre à jour</button>
            <?php else: ?>
                <button type="submit" name="add_user" class="btn btn-primary md:col-span-2">Enregistrer</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- AFFICHAGE -->
    <div class="space-y-4">
        <h2 class="text-xl font-semibold mb-4">Liste des utilisateurs</h2>
        <?php foreach ($utilisateurs as $u): ?>
            <div class="card bg-white shadow p-4 flex items-center gap-4">
                <div class="avatar">
                    <div class="w-16 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                        <img src="<?= $u['image'] ? htmlspecialchars($u['image']) : 'https://via.placeholder.com/100' ?>" alt="Image">
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-lg"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></h3>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($u['email']) ?></p>
                    <?php if ($u['commentaire']): ?>
                        <p class="mt-2"><?= nl2br(htmlspecialchars($u['commentaire'])) ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-400"><?= $u['date_creation'] ?></p>
                </div>
                <div class="flex gap-2">
                    <a href="?edit=<?= $u['id'] ?>" class="btn btn-sm btn-info">Modifier</a>
                    <form method="post" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                        <input type="hidden" name="delete_id" value="<?= $u['id'] ?>">
                        <button class="btn btn-sm btn-error text-white">Supprimer</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
