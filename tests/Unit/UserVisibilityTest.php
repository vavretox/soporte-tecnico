<?php

namespace Tests\Unit;

use App\Models\Bitacora;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserVisibilityTest extends TestCase
{
    public function test_admin_can_view_any_ticket_and_bitacora(): void
    {
        $admin = (new User())->forceFill(['id' => 1, 'role' => 'admin']);
        $ticket = new Ticket(['user_id' => 2, 'assigned_to' => 3, 'department_id' => 4]);
        $bitacora = new Bitacora(['user_id' => 2, 'technician_id' => 3]);

        $this->assertTrue($admin->canViewTicket($ticket));
        $this->assertTrue($admin->canReportTicket($ticket));
        $this->assertTrue($admin->canViewBitacora($bitacora));
    }

    public function test_support_can_view_department_ticket_but_reports_only_assigned_work(): void
    {
        $support = (new User())->forceFill(['id' => 7, 'role' => 'support', 'department_id' => 10]);
        $departmentTicket = new Ticket(['user_id' => 2, 'assigned_to' => null, 'department_id' => 10]);
        $assignedTicket = new Ticket(['user_id' => 2, 'assigned_to' => 7, 'department_id' => 20]);
        $otherTicket = new Ticket(['user_id' => 2, 'assigned_to' => null, 'department_id' => 30]);

        $this->assertTrue($support->isManager());
        $this->assertTrue($support->canViewTicket($departmentTicket));
        $this->assertFalse($support->canViewTicket($otherTicket));
        $this->assertFalse($support->canReportTicket($departmentTicket));
        $this->assertTrue($support->canReportTicket($assignedTicket));
    }

    public function test_support_can_view_tickets_for_multiple_assigned_departments(): void
    {
        $support = (new User())->forceFill(['id' => 7, 'role' => 'support']);
        $support->setRelation('supportDepartments', collect([
            (new Department())->forceFill(['id' => 10]),
            (new Department())->forceFill(['id' => 20]),
        ]));

        $this->assertTrue($support->canViewTicket(new Ticket(['user_id' => 2, 'department_id' => 10])));
        $this->assertTrue($support->canViewTicket(new Ticket(['user_id' => 2, 'department_id' => 20])));
        $this->assertFalse($support->canViewTicket(new Ticket(['user_id' => 2, 'department_id' => 30])));
    }

    public function test_regular_user_only_views_own_records(): void
    {
        $user = (new User())->forceFill(['id' => 5, 'role' => 'user']);

        $this->assertTrue($user->canViewTicket(new Ticket(['user_id' => 5])));
        $this->assertFalse($user->canViewTicket(new Ticket(['user_id' => 6])));
        $this->assertTrue($user->canViewBitacora(new Bitacora(['user_id' => 5])));
        $this->assertFalse($user->canViewBitacora(new Bitacora(['user_id' => 6])));
    }

    public function test_physical_ticket_chat_belongs_to_secretary_not_requester(): void
    {
        $requester = (new User())->forceFill(['id' => 5, 'role' => 'user']);
        $secretary = (new User())->forceFill(['id' => 9, 'role' => 'secretary_dti']);
        $ticket = new Ticket([
            'request_channel' => 'physical',
            'user_id' => 5,
            'created_by_id' => 9,
            'department_id' => 10,
        ]);

        $this->assertFalse($requester->canViewTicket($ticket));
        $this->assertTrue($secretary->canViewTicket($ticket));
        $this->assertTrue($secretary->canReportTicket($ticket));
    }
}
