<?php

require('./echo.php');
require_once('./bibli.php');

// bufferisation des sorties
ob_start();

// démarrage de la session 
session_start();

// affichage de l'entête
fd_entete('Inscription', '..', '../css/style.css');

/**
 * Variables globales aidant à l'identification de problèmes potentiels.
 * $msg_erreurs Stocke toutes les erreurs que chacune des fonctions relève.
 * $co          Connexion unique à la bdd sur toute la page.
 */
$msg_erreurs = '';
$co = connecter();

// affichage du contenu de la page
fdl_formulaire_inscription();

// pied de page
fd_pied();

// fin du script --> envoi de la page 
ob_end_flush();

/**
 * Affichage du contenu principal de la page.
 */
function fdl_formulaire_inscription()
{
    echo '<main>',
        '<section>',
        '<h2>Formulaire d\'inscription</h2>',
        '<p>Pour vous inscrire, remplissez le formulaire ci-dessous.</p>';

    if (isset($_POST['inscription']))
        verification_formulaire();

    echo '<form action="inscription.php" method="post">',
        '<table>',
        '<tr>',
        '<td><label for="pseudo">Choisissez votre pseudo : </label></td>',
        '<td><input type="text" name="pseudo" /></td>',
        '</tr>',

        '<tr>',
        '<td><label for="nom">Votre nom : </td>',
        '<td><input type="text" name="nom" /></td>',
        '</tr>',

        '<tr>',
        '<td><label for="prenom">Votre prénom : </td>',
        '<td><input type="text" name="prenom" /></td>',
        '</tr>',

        '<tr>',
        '<td><label for="date_naissance">Votre date de naissance : </td>',
        '<td><select name="jour">';

    for ($i = 1; $i <= 31; ++$i)
        echo "<option value='$i'>$i</option>";
    echo '</select>',

        fd_creer_liste_mois('mois', 'janvier');

    echo '<select name="annee">';
    for ($i = 2019; $i >= 1950; --$i)
        echo "<option value='$i'>$i</option>";
    echo '</select></tr>',

        '<tr>',
        '<td><label for="email">Votre email : </td>',
        '<td><input type="email" name="email" value="',
        '"/></td>',
        '</tr>',

        '<tr>',
        '<td><label for="passe1">Choisissez un mot de passe : </td>',
        '<td><input type="password" name="passe1" /></td>',
        '</tr>',

        '<tr>',
        '<td><label for="passe2">Répétez le mot de passe : </td>',
        '<td><input type="password" name="passe2" /></td>',
        '</tr>',

        '<tr>',
        '<td><input type="submit" name="inscription" value="S\'inscrire" /></td>',
        '<td><input type="reset" value="Réinitialiser" id="reset" /></td>',

        '</table></form></section></main>';
}

// ***** FONCTIONS PROPRES À INSCRIPTION.PHP ***** //

/**
 * Vérification de l'intégrité du formulaire.
 * Après vérification de chaque champ, s'il en résulte un
 * ou plusieurs erreurs, affichage de ces dernières.
 * Sinon, validation et envoie de l'inscription auprès de la bdd.
 */
function verification_formulaire()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Si l'utilisateur a cliqué sur le bouton d'inscription...
        if (isset($_POST['inscription'])) {

            // Change chaque valeur de $_POST par sa version sécurisée avant traitement
            protection_entrees();

            // Lancement des vérifications des champs
            verif_pseudo();
            verif_noms();
            verif_date();
            verif_email();
            verif_passes();

            $ok = true;

            // Si une erreur est détectée, le formulaire est invalidé
            foreach ($GLOBALS['erreurs'] as $value)
                if ($value == false)
                    $ok = false;

            if (!$ok) {
                global $msg_erreurs;
                $msg_erreurs = $msg_erreurs . '</ul>';
                $message = 'Les erreurs suivantes ont été relevées lors de votre inscription : <ul>';
                affiche_erreur($message, $msg_erreurs);
            } else {
                inscription_dans_bd();
                echo '<p class="succes">Inscription réussie.</p>';
            }
        }
    }
}


// *** FONCTIONS DE VERIFICATION POUR CHAQUE CHAMP *** //

/**
 * Vérifie si le pseudo est correcte.
 * Il doit être :
 *  - non-nul;
 *  - avoir une longueur doit moins 4 caractères;
 *  - ne pas exister dans la base de données.
 * 
 * Inscrit les résultats dans les variables globales :
 *  - $GLOBALS['erreurs'] : est-ce que le champ est valide;
 *  - $msg_erreurs : si le champ est non-valide, inscrit un message
 *      qui sera affiché.
 * Fonctionne de la même manière sur toutes les autres fonctions de vérifications.
 */
function verif_pseudo()
{
    global $msg_erreurs;

    $pseudo = $_POST['pseudo'];

    if (strlen($pseudo) < 4) {
        $msg_erreurs .=  '<li>Le pseudo doit contenir au moins 4 caractères alphanumériques (lettres ou chiffres).</li>';
        $GLOBALS['erreurs']['pseudo'] = false;
    } else if (pseudo_dans_bd($pseudo)) {
        $msg_erreurs .= "<li>Le pseudo <i>$pseudo</i> existe déjà dans la base de donnée.</li>";
        $GLOBALS['erreurs']['pseudo'] = false;
    } else
        $GLOBALS['erreurs']['pseudo'] = true;
}

function verif_noms()
{
    global $msg_erreurs;

    $GLOBALS['erreurs']['nom'] = true;
    $GLOBALS['erreurs']['prenom'] = true;

    if (empty($_POST['nom'])) {
        $msg_erreurs .= '<li>Le nom ne doit pas être vide.</li>';
        $GLOBALS['erreurs']['nom'] = false;
    }

    if (empty($_POST['prenom'])) {
        $msg_erreurs .= '<li>Le prénom ne doit pas être vide.</li>';
        $GLOBALS['erreurs']['prenom'] = false;
    }
}

function verif_date()
{
    global $msg_erreurs;

    $GLOBALS['erreurs']['date'] = true;

    $date_naissance = $_POST['date'] = mktime(10, 10, 0, intval($_POST['mois']), intval($_POST['jour']), intval($_POST['annee']));
    $date_majorite = strtotime("+18 years", $date_naissance);
    $estMajeur = $date_majorite <= mktime(0, 0, 0, date('m'), date('d'), date('Y'));

    if (!$estMajeur) {
        $msg_erreurs .= "<li>Vous devez avoir au moins 18 ans pour vous inscrire.</li>";
        $GLOBALS['erreurs']['date'] = false;
    }
}

function verif_email()
{
    global $msg_erreurs;

    $GLOBALS['erreurs']['email'] = true;

    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $msg_erreurs .= '<li>L\'adresse email saisie n\'est pas dans un format valide.</li>';
        $GLOBALS['erreurs']['email'] = false;
    }
}

function verif_passes()
{
    global $msg_erreurs;

    $GLOBALS['erreurs']['passes'] = true;

    if (empty($_POST['passe1']) || empty($_POST['passe2'])) {
        $msg_erreurs .= '<li>Les mots de passe ne doivent pas être vides.</li>';
        $GLOBALS['erreurs']['passes'] = false;
    } else if ($_POST['passe1'] != $_POST['passe2']) {
        $msg_erreurs .= '<li>Les mots de passes sont différents.</li>';
        $GLOBALS['erreurs']['passes'] = false;
    } else
        $_POST['passe1'] = password_hash($_POST['passe1'], PASSWORD_DEFAULT);
}

// *** FONCTIONS RELATIVES À LA BASE DE DONNÉE *** //

/**
 * Protège chaque entrée de la variable $_POST, et
 * réattribue le résultat renvoyé par la fonction
 * à la valeur de $_POST elle-même.
 */
function protection_entrees()
{
    global $co;
    foreach ($_POST as $key => $value)
        $_POST[$key] = proteger_entree($co, $value);
}

/**
 * Teste si le pseudo existe dans la base de donnée.
 */
function pseudo_dans_bd(&$pseudo)
{
    global $co;
    $sql = "SELECT count(*) as c FROM `utilisateur`
            WHERE utPseudo = \"$pseudo\";";

    $res = $co->query($sql) or bd_erreur($co, $sql);
    $enr = mysqli_fetch_object($res);
    $res->free();

    if ($enr->c == 0)
        return false;
    return true;
}

/**
 * Inscrit l'utilisateur si chaque test est concluant.
 */
function inscription_dans_bd()
{
    global $co;
    $date = date('Ymd', $_POST['date']);
    $sql =  'INSERT INTO `utilisateur` (utPseudo, utNom, utPrenom, utEmail, utPasse, utDateNaissance, utRedacteur)' .
        " VALUES ( '{$_POST['pseudo']}', '{$_POST['nom']}', '{$_POST['prenom']}', '{$_POST['email']}', '{$_POST['passe1']}', '$date', 0);";

    $res = $co->query($sql) or bd_erreur($co, $sql);

    // Si l'inscription a marchée
    $_SESSION['pseudo'] = $_POST['pseudo'];
    $_SESSION['estRedacteur'] = 0;
    $co->close();
}
