<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name')->after('last_name')->nullable();
        });

        $users = \App\User::get();
        foreach ($users as $user) {
            $user->full_name = $user->first_name . ($user->last_name ? ' ' . $user->last_name : '');
            $user->save();
        }

        \DB::unprepared("
            CREATE TRIGGER fill_full_name_on_created_trigger BEFORE INSERT ON users
            FOR EACH ROW
            BEGIN
                SET NEW.full_name = CONCAT(NEW.first_name, ' ', NEW.last_name);
            END
        ");

        \DB::unprepared("
            CREATE TRIGGER fill_full_name_on_updated_trigger BEFORE UPDATE ON users
            FOR EACH ROW
            BEGIN
                SET NEW.full_name = CONCAT(NEW.first_name, ' ', NEW.last_name);
            END
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::unprepared('DROP TRIGGER IF EXISTS fill_full_name_on_created_trigger');
        \DB::unprepared('DROP TRIGGER IF EXISTS fill_full_name_on_updated_trigger');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });
    }
};
