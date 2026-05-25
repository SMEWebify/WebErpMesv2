<?php

namespace Tests\Unit;

use App\Models\Methods\MethodsTools;
use App\Models\Planning\SubAssembly;
use App\Models\Products\Products;
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
        $order = (new Orders())->forceFill([
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

        $line = (new OrderLines())->forceFill([
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
        $this->assertSame('OF55', $job['of_code']);
        $this->assertSame('55', $job['line_ref']);
        $this->assertSame('released', $job['status']);
        $this->assertSame(2, $job['priority']);
        $this->assertSame('2026-03-01', $job['due_date']);
        $this->assertSame('CLI1', $job['customer_code']);
        $this->assertSame('Client One', $job['customer_name']);
        $this->assertSame('Steel', $job['material']);
        $this->assertSame(1.5, $job['thickness']);
        $this->assertSame('SymbolPart', $job['part_type']);
        $this->assertCount(1, $job['tasks']);
        $this->assertSame('CUT', $job['tasks'][0]['operation_code']);
        $this->assertSame('CNC01', $job['tasks'][0]['workcenter_code']);
        $this->assertSame('2026-02-21 08:00:00', $job['tasks'][0]['planned_start_at']);
        $this->assertSame(90, $job['tasks'][0]['planned_time_min']);
    }

    public function test_part_type_is_assembly_when_line_has_sub_assemblies(): void
    {
        $order = new Orders(['id' => 1, 'priority' => 3]);
        $line = new OrderLines(['id' => 1, 'qty' => 1]);
        $line->setRelation('OrderLineDetails', new OrderLineDetails(['thickness' => 2.0]));
        $line->setRelation('Task', new Collection());
        $line->setRelation('SubAssembly', new Collection([new SubAssembly(['ordre' => 1])]));
        $order->setRelation('OrderLines', new Collection([$line]));
        $order->setRelation('companie', null);

        $payload = (new N2PPayloadBuilder())->build($order, ['n2p_send_tasks' => false]);

        $this->assertSame('Assembly', $payload['jobs'][0]['part_type']);
    }

    public function test_part_type_is_standard_when_product_is_purchased(): void
    {
        $order = new Orders(['id' => 1, 'priority' => 3]);
        $line = new OrderLines(['id' => 1, 'qty' => 1]);
        $line->setRelation('OrderLineDetails', null);
        $line->setRelation('Task', new Collection());
        $line->setRelation('SubAssembly', new Collection());

        $product = new Products(['code' => 'BOLT-M6', 'purchased' => 1]);
        $product->setRelation('SubAssembly', new Collection());
        $line->setRelation('Product', $product);

        $order->setRelation('OrderLines', new Collection([$line]));
        $order->setRelation('companie', null);

        $payload = (new N2PPayloadBuilder())->build($order, ['n2p_send_tasks' => false]);

        $this->assertSame('StandardPart', $payload['jobs'][0]['part_type']);
    }

    public function test_part_type_is_symbol_when_cam_file_path_is_sym(): void
    {
        $order = new Orders(['id' => 1, 'priority' => 3]);
        $line = new OrderLines(['id' => 1, 'qty' => 1]);
        $details = new OrderLineDetails(['cam_file_path' => '/nest/parts/PART-A.SYM']);
        $line->setRelation('OrderLineDetails', $details);
        $line->setRelation('Task', new Collection());
        $line->setRelation('SubAssembly', new Collection());

        $product = new Products(['code' => 'PART-A', 'purchased' => 1]);
        $product->setRelation('SubAssembly', new Collection());
        $line->setRelation('Product', $product);

        $order->setRelation('OrderLines', new Collection([$line]));
        $order->setRelation('companie', null);

        $payload = (new N2PPayloadBuilder())->build($order, ['n2p_send_tasks' => false]);

        $this->assertSame('SymbolPart', $payload['jobs'][0]['part_type']);
    }

    public function test_part_type_falls_back_to_standard_when_no_signals(): void
    {
        $order = new Orders(['id' => 1, 'priority' => 3]);
        $line = new OrderLines(['id' => 1, 'qty' => 1]);
        $line->setRelation('OrderLineDetails', null);
        $line->setRelation('Task', new Collection());
        $line->setRelation('SubAssembly', new Collection());
        $line->setRelation('Product', null);

        $order->setRelation('OrderLines', new Collection([$line]));
        $order->setRelation('companie', null);

        $payload = (new N2PPayloadBuilder())->build($order, ['n2p_send_tasks' => false]);

        $this->assertSame('StandardPart', $payload['jobs'][0]['part_type']);
    }
}
