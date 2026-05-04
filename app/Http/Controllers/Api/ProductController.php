<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DocumentCodeGenerator;
use App\Models\Products\Products;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Requests\Api\UpsertProductRequest;

class ProductController extends Controller
{
    public function index()
    {
        return ProductResource::collection(
            Products::with(['service', 'family', 'Unit'])->paginate(10)
        );
    }

    public function show(Products $product)
    {
        $product->load(['service', 'family', 'Unit']);

        return new ProductResource($product);
    }

    public function store(UpsertProductRequest $request, DocumentCodeGenerator $codeGenerator)
    {
        $logger = Log::channel('product_import');

        $logger->info('STORE — payload reçu', [
            'user_id' => Auth::id(),
            'ip'      => $request->ip(),
            'payload' => $request->all(),
        ]);

        try {
            $product = DB::transaction(function () use ($request, $codeGenerator, $logger) {
                $data = $this->resolveReferences($request->validated(), $logger);

                if (empty($data['code'])) {
                    $data['code'] = $codeGenerator->generateDocumentCode('product');
                }

                return Products::create($data);
            });

            $logger->info('STORE — article créé', [
                'product_id' => $product->id,
                'code'       => $product->code,
                'label'      => $product->label,
            ]);

            return (new ProductResource($product->fresh(['service', 'family', 'Unit'])))
                ->response()
                ->setStatusCode(201);

        } catch (\Throwable $e) {
            $logger->error('STORE — échec', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
                'payload' => $request->all(),
            ]);
            throw $e;
        }
    }

    public function update(UpsertProductRequest $request, Products $product)
    {
        $logger = Log::channel('product_import');

        $logger->info('UPDATE — payload reçu', [
            'product_id' => $product->id,
            'code'       => $product->code,
            'user_id'    => Auth::id(),
            'ip'         => $request->ip(),
            'payload'    => $request->all(),
        ]);

        try {
            DB::transaction(function () use ($request, $product, $logger) {
                $data = $this->resolveReferences($request->validated(), $logger);
                $product->update($data);
            });

            $logger->info('UPDATE — article mis à jour', [
                'product_id' => $product->id,
                'code'       => $product->code,
                'label'      => $product->label,
            ]);

            return new ProductResource($product->fresh(['service', 'family', 'Unit']));

        } catch (\Throwable $e) {
            $logger->error('UPDATE — échec', [
                'product_id' => $product->id,
                'user_id'    => Auth::id(),
                'error'      => $e->getMessage(),
                'payload'    => $request->all(),
            ]);
            throw $e;
        }
    }

    /**
     * Résout service_code / family_code / unit_code vers les IDs et applique les défauts :
     * - service : fallback sur le 1er service de type 8 (composant)
     * - family  : fallback sur la 1re famille rattachée au service retenu
     * - unit    : fallback sur MethodsUnits::getDefault()
     * - sold/purchased/tracability_type : défauts métier raisonnables
     */
    private function resolveReferences(array $data, \Psr\Log\LoggerInterface $logger): array
    {
        // Service
        if (empty($data['methods_services_id']) && !empty($data['service_code'])) {
            $service = MethodsServices::where('code', $data['service_code'])->first();
            if ($service) {
                $data['methods_services_id'] = $service->id;
            } else {
                $logger->warning('service_code non trouvé', ['service_code' => $data['service_code']]);
            }
        }
        if (empty($data['methods_services_id'])) {
            $defaultService = MethodsServices::where('type', 8)->orderBy('ordre')->first();
            if ($defaultService) {
                $data['methods_services_id'] = $defaultService->id;
                $logger->info('Fallback service appliqué (type=8 composant)', ['id' => $defaultService->id, 'code' => $defaultService->code]);
            }
        }

        // Family
        if (empty($data['methods_families_id']) && !empty($data['family_code'])) {
            $family = MethodsFamilies::where('code', $data['family_code'])->first();
            if ($family) {
                $data['methods_families_id'] = $family->id;
            } else {
                $logger->warning('family_code non trouvé', ['family_code' => $data['family_code']]);
            }
        }
        if (empty($data['methods_families_id']) && !empty($data['methods_services_id'])) {
            $family = MethodsFamilies::where('methods_services_id', $data['methods_services_id'])->first();
            if ($family) {
                $data['methods_families_id'] = $family->id;
                $logger->info('Fallback family appliqué (1re famille du service)', ['id' => $family->id, 'code' => $family->code]);
            }
        }

        // Unit
        if (empty($data['methods_units_id']) && !empty($data['unit_code'])) {
            $unit = MethodsUnits::where('code', $data['unit_code'])->first();
            if ($unit) {
                $data['methods_units_id'] = $unit->id;
            } else {
                $logger->warning('unit_code non trouvé', ['unit_code' => $data['unit_code']]);
            }
        }
        if (empty($data['methods_units_id'])) {
            $data['methods_units_id'] = MethodsUnits::getDefault()?->id;
        }

        // Défauts métier
        $data['sold']             ??= 1;
        $data['purchased']        ??= 1;
        $data['tracability_type'] ??= 1;

        // Champs internes (non persistés sur products)
        unset($data['service_code'], $data['family_code'], $data['unit_code']);

        return $data;
    }
}
