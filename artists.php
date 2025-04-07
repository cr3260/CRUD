<?php
include_once "db_connect.php";

// Handle delete operation
if(isset($_GET["delete_id"])) {
    $artist_id = (int)$_GET["delete_id"];
    $sql = "DELETE FROM artist WHERE artist_id = $artist_id";
    if(mysqli_query($conn, $sql)) {
        header("location: artist.php?delete_success=1");
        exit();
    } else {
        header("location: artist.php?error=" . urlencode(mysqli_error($conn)));
        exit();
    }
}

// Handle form submission for add/edit
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    
    // Validate all required fields
    $required_fields = [
        'artist_id' => 'Artist ID',
        'artist_name' => 'Artist name',
        'artist_birthCountry' => 'Birth country'
    ];
    
    foreach($required_fields as $field => $name) {
        if(empty($_POST[$field])) {
            $errors[] = "$name is required";
        }
    }
    
    if(empty($errors)) {
        $artist_id = (int)$_POST['artist_id'];
        $artist_name = mysqli_real_escape_string($conn, $_POST['artist_name']);
        $artist_birthCountry = (int)$_POST['artist_birthCountry'];

        if(isset($_POST["add"])) {
            $sql = "INSERT INTO artist (artist_id, artist_name, artist_birthCountry) 
                    VALUES ($artist_id, '$artist_name', $artist_birthCountry)";

            if(mysqli_query($conn, $sql)) {
                header("location: artist.php?add_success=1");
                exit();
            } else {
                $errors[] = "Error adding record: " . mysqli_error($conn);
            }
        } elseif(isset($_POST["update"])) {
            $sql = "UPDATE artist SET 
                    artist_name='$artist_name', 
                    artist_birthCountry=$artist_birthCountry 
                    WHERE artist_id=$artist_id";

            if(mysqli_query($conn, $sql)) {
                header("location: artist.php?update_success=1");
                exit();
            } else {
                $errors[] = "Error updating record: " . mysqli_error($conn);
            }
        }
    }
}

// Fetch all artists
$artists = [];
$sql = "SELECT a.*, c.country_name 
        FROM artist a
        LEFT JOIN country c ON a.artist_birthCountry = c.country_id
        ORDER BY a.artist_id";
$result = mysqli_query($conn, $sql);
if($result) {
    $artists = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Fetch countries for dropdown
$countries = [];
$sql_countries = "SELECT * FROM country ORDER BY country_name";
$result_countries = mysqli_query($conn, $sql_countries);
if($result_countries) {
    $countries = mysqli_fetch_all($result_countries, MYSQLI_ASSOC);
}

// Get the next available artist ID
$next_id = 1;
$sql_max_id = "SELECT MAX(artist_id) as max_id FROM artist";
$result_max_id = mysqli_query($conn, $sql_max_id);
if($result_max_id && mysqli_num_rows($result_max_id) > 0) {
    $row = mysqli_fetch_assoc($result_max_id);
    $next_id = $row['max_id'] + 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Artists</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .action-btn { min-width: 70px; }
        .was-validated .form-control:invalid,
        .was-validated .form-select:invalid {
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <?php if(isset($_GET['add_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Artist added successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif(isset($_GET['update_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Artist updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif(isset($_GET['delete_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Artist deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif(isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    <?php foreach($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Manage Artists</h1>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <!-- Add/Edit Form -->
        <div class="card mb-4">
            <div class="card-header">
                <?php echo isset($_GET['edit_id']) ? 'Edit Artist' : 'Add New Artist'; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="artist.php" class="needs-validation" novalidate>
                    <?php
                    $edit_artist = null;
                    if(isset($_GET['edit_id'])) {
                        $edit_id = (int)$_GET['edit_id'];
                        foreach($artists as $artist) {
                            if($artist['artist_id'] == $edit_id) {
                                $edit_artist = $artist;
                                break;
                            }
                        }
                    }
                    ?>
                    <div class="mb-3">
                        <label for="artist_id" class="form-label">Artist ID</label>
                        <input type="number" class="form-control" id="artist_id" name="artist_id" 
                               value="<?php echo $edit_artist ? htmlspecialchars($edit_artist['artist_id']) : $next_id; ?>" 
                               <?php echo $edit_artist ? 'readonly' : ''; ?> required>
                        <div class="invalid-feedback">
                            Please provide an artist ID
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="artist_name" class="form-label">Artist Name</label>
                        <input type="text" class="form-control" id="artist_name" name="artist_name" 
                               value="<?php echo $edit_artist ? htmlspecialchars($edit_artist['artist_name']) : ''; ?>" 
                               required>
                        <div class="invalid-feedback">
                            Please provide an artist name
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="artist_birthCountry" class="form-label">Birth Country</label>
                        <select class="form-select" id="artist_birthCountry" name="artist_birthCountry" required>
                            <option value="">Select Country</option>
                            <?php foreach($countries as $country): ?>
                                <option value="<?php echo $country['country_id']; ?>"
                                    <?php echo ($edit_artist && $edit_artist['artist_birthCountry'] == $country['country_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($country['country_name'] . ' (' . $country['country_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Please select a country
                        </div>
                    </div>
                    <button type="submit" name="<?php echo $edit_artist ? 'update' : 'add'; ?>" 
                            class="btn btn-primary">
                        <?php echo $edit_artist ? 'Update' : 'Add'; ?>
                    </button>
                    <?php if($edit_artist): ?>
                        <a href="artist.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Artists Table -->
        <div class="card">
            <div class="card-header">
                Artists List
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Birth Country</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($artists as $artist): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($artist['artist_id']); ?></td>
                                <td><?php echo htmlspecialchars($artist['artist_name']); ?></td>
                                <td><?php echo htmlspecialchars(($artist['country_name'] ?? 'N/A') . ' (' . $artist['artist_birthCountry'] . ')'); ?></td>
                                <td>
                                    <a href="artist.php?edit_id=<?php echo $artist['artist_id']; ?>" 
                                       class="btn btn-sm btn-warning action-btn">Edit</a>
                                    <a href="artist.php?delete_id=<?php echo $artist['artist_id']; ?>" 
                                       class="btn btn-sm btn-danger action-btn" 
                                       onclick="return confirm('Are you sure you want to delete this artist?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable Bootstrap form validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
