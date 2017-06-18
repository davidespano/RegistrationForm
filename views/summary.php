<!DOCTYPE html>
<html>
    <head>
        <title>Registration - summary </title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <script src="js/jquery-3.2.1.min.js"></script>
        <link rel="stylesheet" href="css/bootstrap.min.css">
        <link rel="stylesheet" href="css/bootstrap-theme.min.css" >
        <link rel="stylesheet" href="css/custom.css" >
        <script src="js/bootstrap.min.js"></script>
        
        <script src="js/personal.js"></script>
    </head>
    <body>
        <div class="container">
            
            <form class="form-horizontal" method="post" action="index.php">
                <img src="<?= $keys->logo ?>" alt="conference logo" class="img-fluid" style="width: 100%"/>
                
                <h2>Registration summary</h2>
                <div class="well">
                    <h3>Personal Info</h3>
                    <?=$p->prefix ?> <?=$p->firstname ?> <?=$p->middlename ?> <?=$p->lastname ?> <br/>
                    <?=$p->company ?> <br/>
                    <?=$p->addressline1 ?> <?=$p->addressline2 ?>, <?=$p->zip ?>, <?=$p->city ?>, <?=$p->country ?><br/>
                    
                    <h3>Dietary requirements</h3>
                    <?= $p->getDietaryString() ?>
                    
                    <h3>Fees</h3>
                    <ul>
                        <li><?=$p->getRegType()->title ?> (<?=$p->getRegType()->cost ?> €)</li>
                        <?php foreach ($p->getWorkshops() as $w) { ?>
                        <li><?=$w->title ?> </li>
                        <?php } ?>
                        <?php foreach ($p->getExtras() as $e) { ?>
                        <li><?=$e->title ?> (<?=$e->cost ?> €)</li>
                        <?php } ?>
                    </ul>
                    <strong style="font-size:2.0em">Total: <?=$p->getTotalCost() ?>€ </strong>
                    
                    
                    
                    
                    
                </div>
                <!-- TODO eliminare prima di inviare a numera -->
                <input type="hidden" name="step" value="s4">
                <input type="hidden" name="conf" value="<?= $keys->conf ?>">
                <input type="hidden" name="partId" value="<?= $p->id ?>">
                <!-- input per numera -->
                <input type="hidden" name="pol_vendor" value="<?= $keys->vendor ?>">
                <input type="hidden" name="pol_keyord" value="<?= $p->id ?>">
                <button id="continue" type="submit" class="btn btn-success center-block">Pay with credit card (<?=$p->getTotalCost() ?>€)</button>
                
            </form>
        </div>
    </body>
</html>

