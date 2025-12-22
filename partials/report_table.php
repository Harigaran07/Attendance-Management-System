<?php if (!empty($search_results)) { ?>
<div class="table-responsive mt-4">
    <table class="table table-bordered text-center">
        <thead class="table-primary">
            <tr>
                <th>Register No</th>
                <th>Name</th>
                <th>Date</th>
                <th>Subject</th>
                <th>Period</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($search_results as $row) { ?>
            <tr>
                <td><?= $row['register_no'] ?></td>
                <td><?= $row['name'] ?></td>
                <td><?= $row['date'] ?></td>
                <td><?= $row['subject_name'] ?: '<span class="text-muted">N/A</span>' ?></td>
                <td><?= $row['period'] ?></td>
                <td class="<?= $row['status'] == 'Present' ? 'text-success' : 'text-danger' ?>">
                    <?= $row['status'] ?>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php } ?>
