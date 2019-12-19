
<?php
$data = array();
if (($handle = fopen("enquete.csv", "r")) !== false) {
    while (($row = fgetcsv($handle, 1000, ",")) !== false) {
        array_push($data, $row);
    }
    fclose($handle);
}

//Préparation des données
$ratings = array();
$dishes = array();
$likedDishes = array();

$counter = 0;
for ($indexColumn = 1 ; $indexColumn < count($data[0]) ; $indexColumn++ ){

//On récupère les noms des plats de pâtes et on le stock dans le tableau $dishes
  $columnTitle = strtolower($data[0][$indexColumn]);
  if(preg_match('/avez-vous déjà goûté au plat "([a-zéèêàâïîôûÿœ]+ ?)+" \?/', $columnTitle)){
    preg_match('/"([a-zéèêàâïîôûÿœ]+ ?)+"/', $columnTitle, $matches);
    array_push($dishes, $matches[0]);

  }
//On récupère les notes de chaque plat. Si le plat des pâtes n'est pas noté par l'utilisateur on lui attribut -1
  else if(preg_match('/si oui, donnez une appréciation à ce plat/', $columnTitle)){
    $indexColumnRatings = count($dishes)-1;
    for($indexRow = 1 ; $indexRow < count($data) ; $indexRow++){
      $ratings[$indexRow-1][$indexColumnRatings] = empty($data[$indexRow][$indexColumn]) ? -1.0 : floatval($data[$indexRow][$indexColumn]);
    }
  }
//On stock le plat de pâtes suggéré par les utilisateurs dans le tableau $likedDishes. Si l'utilisateur n'a pas de suggestion on lui attribut "Aucun"
  else if(preg_match('/titre de votre plat de pâtes/', $columnTitle)){
    for($indexRow = 1 ; $indexRow < count($data) ; $indexRow++){
      $likedDishes[$indexRow - 1] = empty($data[$indexRow][$indexColumn]) ? 'Aucun' : $data[$indexRow][$indexColumn];
    }
  }
}

//calcul des moyennes des utilisateurs
$meansRatings = array ();
for($indexRow = 0 ; $indexRow < count($ratings) ; $indexRow++){
  $ratingsSum = 0;
  $nbRatings = 0;
  for($indexColumn = 0; $indexColumn < count($ratings[0]) ; $indexColumn++){
    if($ratings[$indexRow][$indexColumn] >= 0){
      $ratingsSum += $ratings[$indexRow][$indexColumn];
      $nbRatings++;
    }
  }
  array_push($meansRatings, $ratingsSum/$nbRatings);
}

//fonction de la corélation de pearson
function pearson($userA, $userB, $meanA, $meanB){
  $covariance = 0;
  $squaredVarianceA = 0;
  $squaredVarianceB = 0;
  for($index = 0 ; $index < count($userA) ; $index++){
    if($userA[$index] >=0 and $userB[$index] >= 0) {
      $covariance += ($userA[$index] - $meanA) * ($userB[$index] - $meanB);
      $squaredVarianceA += pow(($userA[$index] - $meanA), 2);
      $squaredVarianceB += pow(($userB[$index] - $meanB), 2);
    }
  }
  return $covariance / (sqrt($squaredVarianceA) * sqrt($squaredVarianceB));
}

//création de la matrice de pearson
$pearsonMatrix = array();

for($indexA = 0 ; $indexA < count($ratings); $indexA++){
  for($indexB = 0 ; $indexB < count($ratings); $indexB++){
    $pearsonMatrix[$indexA][$indexB] = pearson($ratings[$indexA], $ratings[$indexB], $meansRatings[$indexA], $meansRatings[$indexB]);
  }
}

//Plat qui va être suggéré par l'utilisateur
$nearestUsers = array();
for($indexRow = 0 ; $indexRow < count($pearsonMatrix) ; $indexRow++ ){
  $maxCoefficient = 0;
  $nearestUser = 0;
  for($indexColumn = 0 ; $indexColumn < count($pearsonMatrix[0]); $indexColumn++){
    if($maxCoefficient < $pearsonMatrix[$indexRow][$indexColumn] && $indexRow != $indexColumn){
      $maxCoefficient = $pearsonMatrix[$indexRow][$indexColumn];
      $nearestUser = $indexColumn;
    }
  }
  $nearestUsers[$indexRow] = $nearestUser;
}

 ?>
<!-- affichage des résultats -->
 <!doctype html>
 <html lang="fr">
   <head>
     <!-- Required meta tags -->
     <meta charset="utf-8">
     <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

     <!-- Bootstrap CSS -->
     <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

     <title>Pâtes</title>
   </head>
   <body>
     <div class="container-fluid">
       <div class="row">
         <div class="col-12">
           <h1>Ratings</h1>
           <div class="table-responsive">
             <table class="table">
               <thead>
                 <tr>
                   <th scope="col">user id</th>
                   <?php
                   for ($index = 0; $index < count($dishes); $index++) {
                     echo sprintf('<th scope="col">%s</th>', substr($dishes[$index], 1, strlen($dishes[$index]) - 2));
                   }
                   ?>
                 </tr>
               </thead>
               <tbody>
                 <?php
                 for ($indexRow = 0; $indexRow < count($ratings); $indexRow++) {
                   echo "<tr>";
                   echo sprintf('<th scope="row">%d</th>', $indexRow);
                   for ($indexColumn = 0; $indexColumn < count($ratings[$indexRow]); $indexColumn++) {
                      echo sprintf('<td>%.0f</td>', $ratings[$indexRow][$indexColumn]);
                   }
                   echo "</tr>";
                 }
                 ?>
               </tbody>
             </table>
           </div>
         </div>
       </div>
     </div>

     <div class="container-fluid">
       <div class="row">
         <div class="col-12">
           <h1>Pearson</h1>
           <div class="table-responsive">
             <table class="table">
               <thead>
                 <tr>
                   <th scope="col">#</th>
                   <?php
                   for($index = 0 ; $index < count($pearsonMatrix) ; $index++){
                     echo sprintf('<th scope="col">%d</th>', $index);
                   }
                   ?>
                 </tr>
               </thead>
               <tbody>
                 <?php
                 for($indexRow = 0; $indexRow < count($pearsonMatrix); $indexRow++){
                   echo '<tr>';
                   echo sprintf('<th scope="row">%d</th>', $indexRow);
                   for($indexColumn = 0; $indexColumn < count($pearsonMatrix[$indexRow]) ; $indexColumn++){
                     echo sprintf('<td>%.3f</td>', $pearsonMatrix[$indexRow][$indexColumn]);
                   }
                   echo '</tr>';
                 }
                 ?>
               </tbody>
             </table>
           </div>
         </div>
       </div>
     </div>

     <div class="container-fluid">
       <div class="row">
         <div class="col-12">
           <h1>Pearson</h1>
           <div class="table-responsive">
             <table class="table">
               <thead>
                 <tr>
                   <th scope="col">User Id</th>
                   <th scope="col">Suggestion de plat</th>
                   <th scope="col">Suggéré par</th>
                   <th scope="col">Coefficient de pearson</th>
                 </tr>
               </thead>
               <tbody>
                 <?php
                 for($index = 0 ; $index < count($nearestUsers); $index++){
                   echo '<tr>';
                   echo sprintf('<td>%d</td>', $index);
                   echo sprintf('<td>%s</td>', $likedDishes[$nearestUsers[$index]]);
                   echo sprintf('<td>%d</td>', $nearestUsers[$index]);
                   echo sprintf('<td>%.3f</td>', $pearsonMatrix[$index][$nearestUsers[$index]]);
                   echo '</tr>';
                 }
                 ?>
               </tbody>
             </table>
           </div>
         </div>
       </div>
     </div>


     <!-- Optional JavaScript -->
     <!-- jQuery first, then Popper.js, then Bootstrap JS -->
     <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
     <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
     <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
   </body>
 </html>
