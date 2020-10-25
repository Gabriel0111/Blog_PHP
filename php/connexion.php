<?php

require_once('./echo.php');
require_once('./bibli.php');

// bufferisation des sorties
ob_start();

// démarrage de la session 
session_start();

// affichage de l'entête
fd_entete('Connexion', '..', '../css/style.css');

// affichage du contenu de la page
fdl_formulaire_connexion();

// pied de page
fd_pied();

// fin du script --> envoi de la page 
ob_end_flush();

/**
 * Affichage du contenu principal de la page.
 */
function fdl_formulaire_connexion()
{
    echo '<main>',
        '<section>',
        '<h2>Formulaire de connexion</h2>',
        '<p>Pour vous indentifier, remplissez le formulaire ci-dessous.</p>';

    verification_pseudo();

    echo '<form action="connexion.php" method="post">',
        '<table>',
        '<tr>',
        '<td><label for="pseudo">Pseudo : </label></td>',
        '<td><input type="text" name="pseudo" /></td>',
        '</tr>',

        '<tr>',
        '<td><label for="password">Mot de passe : </label></td>',
        '<td><input type="password" name="password" /></td>',
        '</tr>',

        '<tr>',
        '<td><input type="submit" name="se_connecter" value="Se connecter" /></td>',
        '<td><input type="reset" value="Annuler" /></td>',
        '</tr>',

        '</table>',
        '</form>';

    echo '<p>Pas encore inscrit ? N\'attendez pas, <a href="inscription.php" title="Inscription">inscrivez-vous</a> !</p>',
        '</section></main>';
}

// ***** FONCTIONS PROPRES À CONNEXION.PHP ***** //

/**
 * Vérifie si les champs ne sont pas vides
 * et que la combinaison pseudo/mot de passe est valide.
 */
function verification_pseudo()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $message = 'Erreur d\'authentification.';

        // Si l'utilisateur n'a rien rentré
        if (isset($_POST['se_connecter']) && (empty($_POST['pseudo']) || empty($_POST['password']))) {
            affiche_erreur($message, 'Vous devez saisir un pseudo et un mot de passe pour continuer.');
        } else {

            // On vérfie le mot de passe entré. S'il est correct, la fonction
            // retournera le pseudo et la valeur utRedacteur.
            // La protection est faite dans la fonction verifier_passe.
            $ok = verifier_passe($_POST['pseudo'], $_POST['password']);

            if ($ok)
                auth_reussie($ok);
            else
                affiche_erreur($message, 'Utilisateur inconnu ou mot de passe incorrect.');
        }
    }
}


/**
 * Redirige l'utilisateur vers la page demandée
 * et enregistre ses informations personnelles.
 * 
 * @param string    $pseudo     Pseudo à enregistrer
 */
function auth_reussie(&$data)
{
    $_SESSION['estRedacteur'] = $data[0];
    $_SESSION['pseudo'] = $data[1];

    header('Location: actus.php');
}
