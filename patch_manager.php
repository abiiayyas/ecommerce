--- resources/views/components/cms/landing-page/⚡manager/manager.php
+++ resources/views/components/cms/landing-page/⚡manager/manager.php
@@ -51,9 +51,20 @@
             ->get(['id', 'name']);
     }
 
-    public function create(): void
+    public function create()
     {
-        $this->resetForm();
+        $this->authorizeOperator();
+        $product = Product::query()->first();
+        if (! $product) {
+            $this->dispatch('toast', type: 'error', message: 'Silakan buat produk terlebih dahulu.');
+            return;
+        }
+        $page = LandingPage::query()->create([
+            'product_id' => $product->id,
+            'slug' => 'draft-' . uniqid(),
+            'headline' => 'Draft Landing Page',
+            'is_active' => false,
+        ]);
+        return redirect()->route('cms.landing-page.builder', ['id' => $page->id]);
     }
 
     public function edit(int $id): void
