<?php

namespace Tests\Unit;

use App\Models\Methods\MethodsTools;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\N2P\N2PPayloadBuilder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class N2PPayloadBuilderTest extends TestCase
{
    public function test_builds_jobs_with_tasks(): void
    {
        $order = new Orders([
            'id' => 10,
            'code' => 'ORD-1001',
            'uuid' => 'uuid-123',
            'priority' => 2,
            'validity_date' => Carbon::parse('2026-02-20'),
        ]);

        $company = new class {
            public string $code = 'CLI1';
            public string $label = 'Client One';
        };

        $line = new OrderLines([
            'id' => 55,
            'orders_id' => $order->getKey(),
            'code' => 'LINE-1',
            'qty' => 10,
            'delivery_date' => Carbon::parse('2026-03-01'),
            'comment' => 'Line note',
        ]);

        $details = new OrderLineDetails([
            'material' => 'Steel',
            'thickness' => 1.5,
        ]);

        $tool = new MethodsTools(['code' => 'CNC01']);

        $task = Mockery::mock('App\Models\Planning\Task')->makePartial();
        $task->shouldReceive('TotalTime')->andReturn(1.5);
        $task->code = 'CUT';
        $task->label = 'Laser cut';
        $task->start_date = Carbon::parse('2026-02-21 08:00:00');
        $task->end_date = Carbon::parse('2026-02-21 10:00:00');
        $task->seting_time = 1.0;
        $task->unit_time = 0.5;
        $task->setRelation('MethodsTools', $tool);

        $line->setRelation('OrderLineDetails', $details);
        $line->setRelation('Task', new Collection([$task]));

        $order->setRelation('OrderLines', new Collection([$line]));
        $order->setRelation('companie', $company);

        $builder = new N2PPayloadBuilder();

        $payload = $builder->build($order, [
            'n2p_job_status_on_send' => 'released',
            'n2p_priority_default' => 3,
            'n2p_send_tasks' => true,
        ]);

        $this->assertArrayHasKey('jobs', $payload);
        $job = $payload['jobs'][0];
        $this->assertSame('ORD-1001', $job['of_code']);
        $this->assertSame('55', $job['line_ref']);
        $this->assertSame('released', $job['status']);
        $this->assertSame(2, $job['priority']);
        $this->assertSame('2026-03-01', $job['due_date']);
        $this->assertSame('CLI1', $job['customer_code']);
        $this->assertSame('Client One', $job['customer_name']);
        $this->assertSame('Steel', $job['material']);
        $this->assertSame(1.5, $job['thickness']);
        $this->assertCount(1, $job['tasks']);
        $this->assertSame('CUT', $job['tasks'][0]['operation_code']);
        $this->assertSame('CNC01', $job['tasks'][0]['workcenter_code']);
        $this->assertSame('2026-02-21 08:00:00', $job['tasks'][0]['planned_start_at']);
        $this->assertSame(90, $job['tasks'][0]['planned_time_min']);
    }
}
