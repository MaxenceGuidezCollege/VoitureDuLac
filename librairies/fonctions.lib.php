<?php
// Fonction d'aide au debug
function pp($obj, $die = true){
    echo "<pre>";
    print_r ($obj);
    echo "</pre>";
    if($die){
        die;
    }
}
// Fonction qui permet de faire une connexion avec la base de données séléctionnée.
function ConnecterBd(&$bd){
    try {
        $bd = new PDO('mysql:host=localhost; dbname=voitureDuLac; charset=utf8', 'root', 'infoMac420');
//        $bd = new PDO('mysql:host=localhost; dbname=maxence_voitureDuLac; charset=utf8', 'maxence_root', 'infoMac420');
        $bd->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch(Exception $e) {
        echo "Echec : " . $e->getMessage();
    }
}
// Fonction qui permet de connecter un usager.
function ConnecterUsager($bd, $courriel, $mdp, $lang){

    $json = obtenirJson($lang);

    $req = $bd->prepare("SELECT * FROM usager WHERE courriel = ?;");
    $req->execute([$courriel]);
    $nb = $req->rowCount();
    if($nb == 0){
        return "<script>document.getElementById('erreur').textContent = '".$json['login_error_password']."';</script>";
    }
    else{
        $ligne = $req->fetch();
        if(password_verify($mdp, $ligne['motPasse'])){
            return null;
        }
        else{
            return "<script>document.getElementById('erreur').textContent = '".$json['login_error_password']."';</script>";
        }
    }
}
// Fonction qui permet de compter le nombre de voitures.
function CompterVoitures($bd){
    $reqCount = $bd->prepare("SELECT COUNT(idVoiture) FROM voiture;");
    $reqCount->execute();
    return $reqCount->fetch()[0];
}
// Fonction qui permet de compter le nombre de réservations.
function CompterReservations($bd){
    $reqCount = $bd->prepare("SELECT COUNT(idReservation) FROM reservation WHERE dateFin >= current_date();");
    $reqCount->execute();
    return $reqCount->fetch()[0];
}
// Fonction qui permet de compter le nombre de factures.
function CompterFactures($bd){
    $reqCount = $bd->prepare("SELECT COUNT(idFacture) FROM facture;");
    $reqCount->execute();
    return $reqCount->fetch()[0];
}
// Fonction qui permet d'afficher la liste des voitures.
function AfficherVoitures($bd, $lang){

    $json = obtenirJson($lang);
    $nameDescription = "description_".$lang;

    $reqVoitures = "SELECT * FROM voiture";
    $resultatVoitures = $bd->query($reqVoitures);
    $resultatVoitures->setFetchMode(PDO::FETCH_OBJ);

    while($ligne = $resultatVoitures->fetch( )){

        print(" <div class='voiture'>
                    <img src='images/".$ligne->idVoiture.".jpg' alt='Image de ".$ligne->nomVoiture."'>
                    <div>
                        <h4>".$ligne->nomVoiture."</h4>
                        <p>".$json['cars_brand']." : ".$ligne->marque."</p>
                        <p>".$json['cars_year']." : ".$ligne->annee."</p>
                        <p>".$ligne->$nameDescription."</p>
                        <a href='reservation.php?select=".$ligne->idVoiture."'>".$json['cars_book_btn']."</a>
                    </div>
                </div>");
    }

    $resultatVoitures->closeCursor( );
}
// Fonction qui permet d'afficher la liste des radio boutons des voitures pour la page de réservation.
function AfficherRadioVoitures($bd, $selection){

    $reqVoitures = "SELECT * FROM voiture";
    $resultatVoitures = $bd->query($reqVoitures);
    $resultatVoitures->setFetchMode(PDO::FETCH_OBJ);

    while($ligne = $resultatVoitures->fetch( )){

        if($ligne->idVoiture == $selection){
            $checked = "checked";
        }
        else{
            $checked = "";
        }

        print(" <input type='radio' name='voitures' id='radio".$ligne->nomVoiture."' value='".$ligne->idVoiture."' $checked required>
                <label for='radio".$ligne->nomVoiture."'>".$ligne->nomVoiture."</label>");
    }

    $resultatVoitures->closeCursor( );
}
// Fonction qui permet d'obtenir le json.
function obtenirJson($lang){
    $contenu_json = file_get_contents('lang/'.$lang.'.json');

    return json_decode($contenu_json, true);
}
// Fonction qui vérifie si la reservation séléctionnée par le client est possible.
function VerifierReservation($bd, $dateDebut, $courriel, $idVoiture, $lang){

    $json = obtenirJson($lang);

    $reqReserv = "SELECT * FROM reservation
                            WHERE noVoiture = '$idVoiture'
                                AND dateDebut <= '$dateDebut'
                                AND dateFin > '$dateDebut';";
    $resReserv = $bd->query($reqReserv);
    $nbReserv = $resReserv->rowCount();
    if($nbReserv != 0){
        return "<script>document.getElementById('erreur').textContent =
            '".$json['booking_error_car']."';</script>";

    }

    $reqClient = "SELECT * FROM client WHERE courriel = '$courriel'";
    $resClient = $bd->query($reqClient);
    $nbClient = $resClient->rowCount();
    if($nbClient == 0){
        return "<script>document.getElementById('erreur').textContent =
            '".$json['booking_error_email']."';</script>";
    }

    return null;
}
// Fonction qui ajoute la reservation séléctionnée par le client.
function AjouterReservation($bd, $dateDebut, $dateFin, $courriel, $idVoiture, $lang){

    $json = obtenirJson($lang);

    $reqClient = "SELECT idClient FROM client WHERE courriel = '$courriel'";
    $resClient = $bd->query($reqClient);
    $resClient->setFetchMode(PDO::FETCH_OBJ);

    $idClient = $resClient->fetchAll()[0]->idClient;

    $reqInsert = "INSERT INTO reservation(noVoiture, noClient, dateDebut, dateFin, status)
                    VALUES ($idVoiture, $idClient, '$dateDebut', '$dateFin', 0);";
    $bd->query($reqInsert);

    echo "<script>alert('La reservation à bien été reçue. Nous communiquerons avec vous pour confirmer la suite.');</script>";
}
// Fonction qui permet d'afficher le menu de modification des voitures.
function AfficherModifierVoiture($bd){
    $req = $bd->prepare("SELECT * FROM voiture;");
    $req->execute();
    $voitures = $req->fetchAll();
    print(" <table id='tableModifierVoiture' class='table'>
                <tr>
                    <th>Nom</th>
                    <th>Marque</th>
                    <th>Année</th>
                    <th>Km</th>
                    <th>Description</th>
                    <th></th>
                </tr>");
    foreach($voitures as $voiture){
        print(" <tr>
                    <td>".$voiture['nomVoiture']."</td>
                    <td>".$voiture['marque']."</td>
                    <td>".$voiture['annee']."</td>
                    <td>".$voiture['km']."</td>
                    <td>".$voiture['description_fr']."</td>
                    <td><a href='modifierVoiture.php?action=modifier&no=".$voiture['idVoiture']."'>Modifier</a></td>
                </tr>");
    }
    print(" </table>
            <p id='aide'>-> Selectionner la voiture à modifier en cliquant sur le lien modifier</p>");
}
// Fonction qui permet d'afficher la voiture seule selectionnée.
function AfficherModifierVoitureSeule($bd, $no){

    $req = $bd->prepare("SELECT * FROM voiture WHERE idVoiture = ?;");
    $req->execute([$no]);
    $ligne = $req->fetch();

    print("<form action='modifierVoiture.php?action=modifier&id=$no' name='formModifierVoiture' method='post'>
            <fieldset>
                <p>Nom de la voiture :</p>
                <input type='text' name='nom' id='nom' value='".$ligne['nomVoiture']."' required>
            </fieldset>

            <fieldset>
                <p>Marque :</p>
                <p>Année :</p>
                <p>Kilomètrage :</p>
                <input type='text' name='marque' id='marque' value='".$ligne['marque']."'>
                <input type='number' name='annee' id=vannee' value='".$ligne['annee']."' required>
                <input type='number' name='km' id='km' value='".$ligne['km']."' required>
            </fieldset>

            <fieldset>
                <p>Description (Français) :</p>
                <p>Description (Anglais) :</p>
                <textarea name='desFr' id='desFr'>".$ligne['description_fr']."</textarea>
                <textarea name='desEn' id='desEn'>".$ligne['description_en']."</textarea>
            </fieldset>

            <fieldset>
                <input type='submit' value='Modifier' class='btn btn-primary'>
                <input type='reset' value='Annuler' onclick='window.location.assign(\"modifierVoiture.php\");' class='btn btn-primary'>
                <p id='erreur'></p>
            </fieldset>
    </form>");
}
// Fonction qui permet d'afficher le menu de suppression des voitures.
function AfficherSupprimerVoiture($bd){
    $req = $bd->prepare("SELECT * FROM voiture;");
    $req->execute();
    $voitures = $req->fetchAll();
    print("
        <form action='supprimerVoiture.php?action=supprimer' name='formSupprimerVoiture' method='post'>
            <fieldset>
                <table id='tableSupprimerVoiture' class='table'>
                    <tr>
                        <th></th>
                        <th>Nom</th>
                        <th>Marque</th>
                        <th>Année</th>
                        <th>Km</th>
                        <th>Description</th>
                    </tr>");
    $cmpt = 0;
    foreach($voitures as $voiture){
        print("     <tr>
                        <td><input type='checkbox' name='chk".$voiture['idVoiture']."' id='chkNo$cmpt'></td>
                        <td>".$voiture['nomVoiture']."</td>
                        <td>".$voiture['marque']."</td>
                        <td>".$voiture['annee']."</td>
                        <td>".$voiture['km']."</td>
                        <td>".$voiture['description_fr']."</td>
                    </tr>");
        $cmpt++;
    }
    print("
                </table>
            </fieldset>
            <fieldset>
                <input type='button' onclick='verifierValeursSupprimer();' value='Supprimer'>
                <input type='reset' value='Annuler'>
                <p id='erreur'></p>
            </fieldset>
        </form>");
}
// Fonction qui permet d'afficher le menu de gestion des réservations.
function AfficherReservations($bd){
    $req = $bd->prepare("SELECT * FROM reservation WHERE dateFin >= current_date();");
    $req->execute();
    $reservs = $req->fetchAll();
    print(" <form action='gestionReservation.php?action=modifier' name='formReservation' method='post'>
                <table id='tableReservation' class='table'>
                    <tr>
                        <th></th>
                        <th>Voiture</th>
                        <th>Date début</th>
                        <th>Date fin</th>
                        <th>Statut</th>
                    </tr>");
    foreach($reservs as $reserv){
        $reqName = $bd->prepare("SELECT nomVoiture FROM voiture WHERE idVoiture = ?;");
        $reqName->execute([$reserv['noVoiture']]);
        $nameVoit = $reqName->fetchAll()[0][0];

        $selectedAttente = "";
        $selectedReserve = "";
        $selectedNonDispo = "";

        switch ($reserv['statut']){
            case 0:
                $selectedAttente = "selected";
                break;
            case 1:
                $selectedReserve = "selected";
                break;
            default:
                $selectedNonDispo = "selected";
                break;
        }

        print(" <tr>
                    <td>
                        <a href='gestionReservation.php?action=supprimer&no=".$reserv['idReservation']."'>
                            <img src='images/supprimer.png' alt='Logo supprimer'>
                        </a>
                    </td>
                    <td>".$nameVoit."</td>
                    <td>".$reserv['dateDebut']."</td>
                    <td>".$reserv['dateFin']."</td>
                    <td>
                        <select name='statut".$reserv['idReservation']."'>
                            <option ".$selectedAttente.">Attente</option>
                            <option ".$selectedReserve.">Réservé</option>
                            <option ".$selectedNonDispo.">Non-disponible</option>
                        </select>
                    </td>
                </tr>");
    }
    print("    </table>
                <input type='submit' value='Mettre à jour les réservations' class='btn btn-primary'>
            </form>");
}
// Fonction qui permet d'obtenir l'id le plus élévé de la table réservation.
function GetMaxIdReservation($bd){
    $req = $bd->prepare("SELECT MAX(idReservation) FROM reservation;");
    $req->execute();
    return $req->fetchAll()[0]['MAX(idReservation)'];
}
// Fonction qui permet d'afficher le menu de gestion des factures.
function AfficherFactures($bd)
{
    $requete = $bd->prepare("SELECT idFacture, nom, prenom, nomVoiture, dateDebut, dateFin, assurance, kmDebut, kmFin
                                FROM facture, client, voiture
                                WHERE noClient = idClient
                                  AND noVoiture = idVoiture
                                ORDER BY nom;");
    $requete->execute();
    print("<h2 class='mb-4'>Gestion des Factures</h2>");
    print(" <form action='#' name='formGestionFacture' method='post'>
                <table class='table table-bordered' >
                    <tr>
                        <th></th>
                        <th> Nom client</th>
                        <th> Voiture</th>
                        <th> Date début</th>
                        <th> Date fin</th>
                        <th> Km départ</th>
                        <th> Km arrivée</th>
                        <th> Assurance</th>
                        <th> &nbsp</th>
                    </tr>");

    while ($ligne = $requete->fetch()) {
        if ($ligne['assurance'] == 0)
            $ass = "non";
        else
            $ass = "oui";

        $img = "images/supprimer.png";
        print("<tr>
                   <td>
                       <a href='#' onclick='verifierSuppressionFacture($ligne[idFacture]);'>
                           <img src='$img' alt='imgSupprimer' height='40' width='30'>
                       </a>
                   </td>
                   <td> $ligne[prenom] $ligne[nom]</td>
                   <td> $ligne[nomVoiture]</td>
                   <td> $ligne[dateDebut]</td>
                   <td> $ligne[dateFin]</td>
                   <td> $ligne[kmDebut]</td>
                   <td> $ligne[kmFin]</td>
                   <td> $ass</td>
                   <td> <a href='gestionFacture.php?action=modifier&id=$ligne[idFacture]'>Mettre à jour</a></td>
               </tr>");
    }

    print("</table>
        </form>");
}
// Fonction qui permet d'afficher la facture seule selectionnée.
function AfficherFactureSeule($bd, $id){

    $req = $bd->prepare("SELECT idFacture, nom, prenom, courriel, nomVoiture, dateDebut, dateFin, assurance, kmDebut, kmFin, montant
                                FROM facture, client, voiture
                                WHERE noClient = idClient
                                  AND noVoiture = idVoiture
                                  AND idFacture = $id
                                ORDER BY nom;");
    $req->execute();
    $ligne = $req->fetch();

    if ($ligne['assurance'] == 0)
        $ass = "";
    else
        $ass = "checked";

    print("<h2 class='mb-4'>Gestion de la Factures : ".$id."</h2>");
    print("<form action='gestionFacture.php?action=modifier&num=$id' name='formModifierFacture' method='post'>
                <div>
                    <a href='#' onclick='print()'>Imprimer</a>
                </div>
                
                <fieldset>
                    <p>Nom du client :</p>
                    <p>Voiture :</p>
                    <input type='text' name='nom' id='nom' value='".$ligne['prenom']." ".$ligne['nom']."' readonly>
                    <input type='text' name='voiture' id='voiture' value='".$ligne['nomVoiture']."' readonly>
                </fieldset>
                
                <fieldset>
                    <p>Kilometrage (debut - fin) :</p>
                    <p>Date (debut - fin) :</p>
                    <div>
                        <input type='number' name='kmDebut' id='kmDebut' value='".$ligne['kmDebut']."' readonly>
                        <input type='number' name='kmFin' id='kmFin' value='".$ligne['kmFin']."'>
                    </div>
                    <div>
                        <input type='date' name='dateDebut' id='dateDebut' value='".$ligne['dateDebut']."' readonly>
                        <input type='date' name='dateFin' id='dateFin' value='".$ligne['dateFin']."'>
                    </div>
                </fieldset>
                
                <fieldset>
                    <div>
                        <p>Assurance :</p>
                        <input type='checkbox' name='assurance' id='assurance' $ass>
                    </div>
                    <div>
                        <p>Montant de la facture :</p>
                        <input type='number' name='montant' id='montant' value='".$ligne['montant']."' readonly>
                    </div>
                </fieldset>
                
                <fieldset>
                    <input type='submit' value='Sauvegarder' class='btn btn-primary'>
                    <input type='button' value='Calculer facture' onclick='calculerFacture($id);' class='btn btn-primary'>
                    <input type='button'
                        value='Envoyer facture'
                        onclick='window.location.assign(\"gestionFacture.php?action=envoyer&cour=".$ligne['courriel']."&id=".$id."\");'
                        class='btn btn-primary'>
                    <input type='reset' value='Annuler' onclick='window.location.assign(\"gestionFacture.php\");' class='btn btn-primary'>
                    <p id='erreur'></p>
                </fieldset>
            </form>");
}
// Fonction qui permet d'envoyer un mail de récap de la facture.
function EnvoyerFacture($bd, $mail, $idFacture){

    $req = $bd->prepare("SELECT * FROM client WHERE courriel = ?;");
    $req->execute([$mail]);
    $ligne = $req->fetch();

    $entete = "From:202130087@collegealma.ca\r\n";
    $objet = "Récapitulatif de facture";

    $texte = "Bonjour ".$ligne['nom']." ".$ligne['prenom'].",
        
Voici une copie de la facture de location :
        ".getRecapFacture($bd, $idFacture)."
        
Nous vous serions reconnaissants de procéder au paiement dans les meilleurs délais.

Merci de l'intéret porter pour nos belles voitures !

Voiture du Lac";

//    if(!mail($mail, $objet, $texte, $entete)){
    if(!mail("202130087@collegealma.ca", $objet, $texte, $entete)){

        print("Le courriel ne s'est pas bien transmis ! Vérifier votre adresse mail.");
    }
    else print("Le courriel s'est bien transmis !");
}
// Fonction qui permet de faire le récap de la facture.
function getRecapFacture($bd, $idFacture){
    $recap = "\n";

    $reqF = $bd->prepare("SELECT * FROM facture WHERE idFacture = ?;");
    $reqF->execute([$idFacture]);
    $ligneF = $reqF->fetch();

    $reqV = $bd->prepare("SELECT nomVoiture FROM voiture WHERE idVoiture = ".$ligneF['noVoiture'].";");
    $reqV->execute();
    $ligneV = $reqV->fetch();

    $recap .= "Voiture : ".$ligneV['nomVoiture']."\n";
    $recap .= "Date de début : ".$ligneF['dateDebut']."\n";
    $recap .= "Date de fin : ".$ligneF['dateFin']."\n";
    $recap .= "Kilométrage de début : ".$ligneF['kmDebut']."\n";
    $recap .= "Kilométrage de fin : ".$ligneF['kmFin']."\n";
    $recap .= "Assurance : ".$ligneF['assurance']."\n";
    $recap .= "Montant de la facture : ".$ligneF['montant']."\n";

    return $recap;
}
?>