@php
    $canCreateProduct = bouncer()->hasPermission('products.create');
@endphp

<v-product-list :data="products"></v-product-list>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-list-template"
    >
        <div class="flex flex-col gap-4">
            {!! view_render_event('admin.leads.create.products.form_controls.table.before') !!}

            @if ($canCreateProduct)
                <div class="flex justify-end">
                    <button
                        type="button"
                        class="text-xs font-semibold text-brandColor hover:underline dark:text-brandColor"
                        @click="openProductModal"
                    >
                        + @lang('admin::app.products.index.create-btn')
                    </button>
                </div>
            @endif

            <div class="block w-full">
                <!-- Table -->
                <x-admin::table>
                    {!! view_render_event('admin.leads.create.products.form_controls.table.head.before') !!}

                    <!-- Table Head -->
                    <x-admin::table.thead>
                        <x-admin::table.thead.tr>
                            <x-admin::table.th style="width: 50%; min-width: 250px;">
                                @lang('admin::app.leads.common.products.product-name')
                            </x-admin::table.th>

                            <x-admin::table.th style="width: 12%;" class="text-center">
                                @lang('admin::app.leads.common.products.quantity')
                            </x-admin::table.th>

                            <x-admin::table.th style="width: 15%;" class="text-center">
                                @lang('admin::app.leads.common.products.price')
                            </x-admin::table.th>

                            <x-admin::table.th style="width: 15%;" class="text-center">
                                @lang('admin::app.leads.common.products.amount')
                            </x-admin::table.th>

                            <x-admin::table.th style="width: 8%;" class="text-right">
                                @lang('admin::app.leads.common.products.action')
                            </x-admin::table.th>
                        </x-admin::table.thead.tr>
                    </x-admin::table.thead>

                    {!! view_render_event('admin.leads.create.products.form_controls.table.head.after') !!}

                    {!! view_render_event('admin.leads.create.products.form_controls.table.body.before') !!}

                    <!-- Table Body -->
                    <x-admin::table.tbody>
                        {!! view_render_event('admin.leads.create.products.form_controls.table.body.product_item.before') !!}

                        <!-- Product Item Vue Component -->
                        <v-product-item
                            v-for='(product, index) in products'
                            :product="product"
                            :key="index"
                            :index="index"
                            @onRemoveProduct="removeProduct($event)"
                        ></v-product-item>

                        {!! view_render_event('admin.leads.create.products.form_controls.table.body.product_item.after') !!}
                    </x-admin::table.tbody>

                    {!! view_render_event('admin.leads.create.products.form_controls.table.body.after') !!}
                </x-admin::table>
            </div>

            {!! view_render_event('admin.leads.create.products.form_controls.table.after') !!}

            <div>
                <!-- Add More Button -->
                <button
                    type="button"
                    class="flex max-w-max items-center gap-2 font-medium text-brandColor"
                    @click="addProduct"
                >
                    <i class="icon-add text-md !text-brandColor"></i>

                    @lang('admin::app.leads.common.products.add-more')
                </button>
            </div>

            @if ($canCreateProduct)
                <!-- Quick Create Product Modal -->
                <Teleport to="body">
                    <x-admin::modal
                        ref="productModal"
                        size="large"
                    >
                        <x-slot:header>
                            <div class="flex items-center justify-between">
                                <p class="text-xl font-semibold text-gray-800 dark:text-white">
                                    @lang('admin::app.products.create.title')
                                </p>
                            </div>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::form
                                v-slot="{ meta, errors, handleSubmit }"
                                as="div"
                                ref="productFormWrapper"
                            >
                                <form
                                    @submit="handleSubmit($event, createProduct)"
                                    ref="productForm"
                                >
                                    <input type="hidden" name="quick_add" value="product" />
                                    <input type="hidden" name="entity_type" value="products" />

                                    <div class="grid gap-4 max-sm:flex-wrap">
                                        <x-admin::attributes
                                            :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                                'entity_type' => 'products',
                                                'quick_add' => 1,
                                            ])"
                                        />
                                    </div>
                                </form>
                            </x-admin::form>
                        </x-slot>

                        <x-slot:footer>
                            <x-admin::button
                                class="primary-button"
                                :title="trans('admin::app.products.create.save-btn')"
                                ::loading="isStoringProduct"
                                ::disabled="isStoringProduct"
                                @click="submitProductForm"
                            />
                        </x-slot>
                    </x-admin::modal>
                </Teleport>
            @endif
        </div>
    </script>

    <script
        type="text/x-template"
        id="v-product-item-template"
    >
        <x-admin::table.thead.tr>
            <!-- Product Name -->
            <x-admin::table.td style="width: 50%; min-width: 250px;">
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::lookup
                        ::src="src"
                        ::name="`${inputName}[name]`"
                        :preload="true"
                        :placeholder="trans('admin::app.leads.common.products.product-name')"
                        @on-selected="(product) => addProduct(product)"
                    />

                    <x-admin::form.control-group.control
                        type="hidden"
                        ::name="`${inputName}[product_id]`"
                        v-model="product.product_id"
                        rules="required"
                        :label="trans('admin::app.leads.common.products.product-name')"
                        :placeholder="trans('admin::app.leads.common.products.product-name')"
                    />

                    <x-admin::form.control-group.error ::name="`${inputName}[product_id]`" />
                </x-admin::form.control-group>
            </x-admin::table.td>

            <!-- Product Quantity -->
            <x-admin::table.td style="width: 12%;" class="text-right">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.control
                        type="inline"
                        ::name="`${inputName}[quantity]`"
                        ::value="product.quantity"
                        rules="required|decimal:4"
                        :label="trans('admin::app.leads.common.products.quantity')"
                        :placeholder="trans('admin::app.leads.common.products.quantity')"
                        @on-change="(event) => product.quantity = event.value"
                        position="center"
                    />
                </x-admin::form.control-group>
            </x-admin::table.td>

            <!-- Price -->
            <x-admin::table.td style="width: 15%;" class="text-right">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.control
                        type="inline"
                        ::name="`${inputName}[price]`"
                        ::value="product.price"
                        rules="required|decimal:4"
                        :label="trans('admin::app.leads.common.products.price')"
                        :placeholder="trans('admin::app.leads.common.products.price')"
                        @on-change="(event) => product.price = event.value"
                        ::value-label="$admin.formatPrice(Number(product.price) || 0)"
                        position="center"
                    />
                </x-admin::form.control-group>
            </x-admin::table.td>

            <!-- Amount -->
            <x-admin::table.td style="width: 15%;" class="text-right">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.control
                        type="inline"
                        ::name="`${inputName}[amount]`"
                        ::value="product.price * product.quantity"
                        rules="required|decimal:4"
                        :label="trans('admin::app.leads.common.products.total')"
                        :placeholder="trans('admin::app.leads.common.products.total')"
                        ::value-label="$admin.formatPrice((Number(product.price) || 0) * (Number(product.quantity) || 0))"
                        :allowEdit="false"
                        position="center"
                    />
                </x-admin::form.control-group>
            </x-admin::table.td>

            <!-- Action -->
            <x-admin::table.td style="width: 8%;" class="text-right">
                <x-admin::form.control-group >
                    <i
                        @click="removeProduct"
                        class="icon-delete cursor-pointer text-2xl"
                    ></i>
                </x-admin::form.control-group>
            </x-admin::table.td>
        </x-admin::table.thead.tr>
    </script>

    <script type="module">
        app.component('v-product-list', {
            template: '#v-product-list-template',

            props: ['data'],

            data: function () {
                return {
                    products: this.data ? this.data : [],

                    isStoringProduct: false,
                }
            },

            created() {
                if (! this.data) {
                    this.addProduct();
                }
            },

            methods: {
                addProduct() {
                    this.products.push({
                        id: null,
                        product_id: null,
                        name: '',
                        quantity: 0,
                        price: 0,
                        amount: null,
                    });
                },

                removeProduct (product) {
                    const index = this.products.indexOf(product);
                    this.products.splice(index, 1);
                },

                openProductModal() {
                    if (this.$refs.productModal) {
                        this.$refs.productModal.open();
                    }
                },

                submitProductForm() {
                    const form = this.$refs.productForm;

                    if (! form) {
                        return;
                    }

                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                    }
                },

                createProduct(params, { setErrors }) {
                    const form = this.$refs.productForm;

                    if (! form) {
                        return;
                    }

                    this.isStoringProduct = true;

                    const formData = new FormData(form);

                    this.$axios.post("{{ route('admin.products.store') }}", formData)
                        .then(response => {
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message
                            });

                            if (this.$refs.productModal) {
                                this.$refs.productModal.close();
                            }

                            form.reset();

                            const newProduct = response.data.data;

                            if (newProduct) {
                                const emptyIndex = this.products.findIndex(p => ! p.product_id && ! p.name);

                                if (emptyIndex !== -1) {
                                    this.products[emptyIndex].product_id = newProduct.id;
                                    this.products[emptyIndex].name = newProduct.name;
                                    this.products[emptyIndex].price = newProduct.price || 0;
                                    this.products[emptyIndex].quantity = newProduct.quantity || 1;
                                } else {
                                    this.products.push({
                                        id: null,
                                        product_id: newProduct.id,
                                        name: newProduct.name,
                                        quantity: newProduct.quantity || 1,
                                        price: newProduct.price || 0,
                                        amount: (Number(newProduct.price) || 0) * (Number(newProduct.quantity) || 1),
                                    });
                                }
                            }
                        })
                        .catch(error => {
                            if (error.response?.status == 422) {
                                setErrors(error.response.data.errors);
                            } else {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message || error.message,
                                });
                            }
                        })
                        .finally(() => {
                            this.isStoringProduct = false;
                        });
                },
            },
        });

        app.component('v-product-item', {
            template: '#v-product-item-template',

            props: ['index', 'product'],

            data() {
                return {
                    products: [],
                }
            },

            computed: {
                inputName() {
                    if (this.product.id) {
                        return "products[" + this.product.id + "]";
                    }

                    return "products[product_" + this.index + "]";
                },

                src() {
                    return "{{ route('admin.products.search') }}";
                },

                params() {
                    return {
                        params: {
                            query: this.product.name,
                        },
                    };
                },
            },

            methods: {
                /**
                 * Add the product.
                 *
                 * @param {Object} result
                 *
                 * @return {void}
                 */
                addProduct(result) {
                    this.product.product_id = result.id;

                    this.product.name = result.name;

                    this.product.price = result.price;

                    this.product.quantity = result.quantity ?? 1;
                },

                /**
                 * Remove the product.
                 *
                 * @return {void}
                 */
                removeProduct () {
                    this.$emit('onRemoveProduct', this.product)
                }
            }
        });
    </script>
@endPushOnce