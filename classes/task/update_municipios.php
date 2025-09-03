<?php


namespace profilefield_brasilufmunicipio\task;

class update_municipios extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('updatemunicipiostask', 'profilefield_brasilufmunicipio');
    }

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute(): void {
        mtrace('Starting to update municipios');
        \profilefield_brasilufmunicipio\api::update_municipios();
    }
}
