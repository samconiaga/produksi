<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProduksiRoleToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    // database/migrations/xxxx_add_produksi_role_to_users_table.php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->enum('produksi_role', ['ADMIN', 'OPERATOR', 'SPV'])
              ->nullable()
              ->after('role');
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('produksi_role');
    });
}
    
}
