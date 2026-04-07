<?php
$name = "Nitin Thakur";
$age = 20;
$city = "Tokyo";
$fav_lang = "PHP";

$details = [
    'Name' => $name,
    'Age' => $age,
    'City' => $city,
    'Favourite Language' => $fav_lang
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>User Profile</h1>
    <p><strong>Name:</strong> <?php echo $name; ?></p>
    <p><strong>Age:</strong> <?php echo $age; ?></p>
    <p><strong>City:</strong> <?php echo $city; ?></p>
    <p><strong>Favourite Language:</strong> <?php echo $fav_lang; ?></p>

    <h2>Variable Dumps</h2>
    <pre><?php var_dump($name); ?></pre>
    <pre><?php var_dump($age); ?></pre>
    <pre><?php var_dump($city); ?></pre>
    <pre><?php var_dump($fav_lang); ?></pre>

    <h2>Personal Details Array</h2>
    <pre><?php print_r($details); ?></pre>
</body>
</html>
