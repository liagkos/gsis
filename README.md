# GSIS helper classes
## class.AFMinfo.php
> Αναζήτηση και ανάκτηση πληροφοριών βάσει ΑΦΜ από το αντίστοιχο Web Service του Taxis. Θα πρέπει να έχετε πάρει πρώτα τους ειδικούς κωδικούς από τη διεύθυνση https://www1.gsis.gr/sgsisapps/tokenservices/protected/displayConsole.htm.

> Χρήση:
 ```php
  $obj = new AFMinfo('user','pass');
 if($obj) {
    $result = $obj->exec('123456789','123456789');
    if($result) {
        ....
    } else {
        echo 'SOAP error';
    }
} else {
    echo 'SOAP error';
}

