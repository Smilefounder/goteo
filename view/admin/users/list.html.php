<?php

use Goteo\Library\Text;

$filters = $this['filters'];

//arrastramos los filtros
$filter = "?status={$filters['status']}&interest={$filters['interest']}";

?>
<a href="/admin/users/add" class="button red">Crear usuario</a>

<div class="widget board">
    <form id="filter-form" action="/admin/users" method="get">
        <label for="status-filter">Mostrar por estado:</label>
        <select id="status-filter" name="status" onchange="document.getElementById('filter-form').submit();">
            <option value="">Todos los estados</option>
        <?php foreach ($this['status'] as $statusId=>$statusName) : ?>
            <option value="<?php echo $statusId; ?>"<?php if ($filters['status'] == $statusId) echo ' selected="selected"';?>><?php echo $statusName; ?></option>
        <?php endforeach; ?>
        </select>

        <label for="interest-filter">Mostrar usuarios interesados en:</label>
        <select id="interest-filter" name="interest" onchange="document.getElementById('filter-form').submit();">
            <option value="">Cualquier interés</option>
        <?php foreach ($this['interests'] as $interestId=>$interestName) : ?>
            <option value="<?php echo $interestId; ?>"<?php if ($filters['interest'] == $interestId) echo ' selected="selected"';?>><?php echo $interestName; ?></option>
        <?php endforeach; ?>
        </select>


        <label for="role-filter">Mostrar usuarios con rol:</label>
        <select id="role-filter" name="role" onchange="document.getElementById('filter-form').submit();">
            <option value="">Cualquier rol</option>
        <?php foreach ($this['roles'] as $roleId=>$roleName) : ?>
            <option value="<?php echo $roleId; ?>"<?php if ($filters['role'] == $roleId) echo ' selected="selected"';?>><?php echo $roleName; ?></option>
        <?php endforeach; ?>
        </select>

        <br />
        <label for="name-filter">Por nombre o email:</label>
        <input id="name-filter" name="name" value="<?php echo $filters['name']; ?>" />
        <input type="submit" name="filter" value="Buscar">

    </form>
</div>

<div class="widget board">
    <?php if (!empty($this['users'])) : ?>
    <table>
        <thead>
            <tr>
                <th></th>
                <th>Alias</th> <!-- view profile -->
                <th>User</th>
                <th>Email</th>
                <th>Estado</th>
                <th>Revisor</th>
                <th>Traductor</th>
<!--                <th></th> -->
                <th></th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($this['users'] as $user) : ?>
            <tr>
                <td><a href="/admin/users/manage/<?php echo $user->id; ?>">[Gestionar]</a></td>
                <td><a href="/user/<?php echo $user->id; ?>" target="_blank" title="Preview"><?php echo $user->name; ?></a></td>
                <td><strong><?php echo $user->id; ?></strong></td>
                <td><?php echo $user->email; ?></td>
                <td><?php echo $user->active ? 'Activo' : 'Inactivo'; ?></td>
                <td><?php echo $user->checker ? 'Revisor' : ''; ?></td>
                <td><?php echo $user->translator ? 'Traductor' : ''; ?></td>
                <td><a href="/admin/users/edit/<?php echo $user->id; ?>">[Editar]</a></td>
                <td><a href="/admin/users/impersonate/<?php echo $user->id; ?>">[Suplantar]</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>

    </table>
    <?php else : ?>
    <p>No se han encontrado registros</p>
    <?php endif; ?>
</div>