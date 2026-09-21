{!! view_render_event('admin.leads.create.contact_person.form_controls.before') !!}

<v-contact-component :data="person"></v-contact-component>

{!! view_render_event('admin.leads.create.contact_person.form_controls.after') !!}

@php
    $canCreatePerson = bouncer()->hasPermission('contacts.persons.create');
    $canCreateOrganization = bouncer()->hasPermission('contacts.organizations.create');

    $organizationAttribute = app('Webkul\Attribute\Repositories\AttributeRepository')->findOneWhere([
        'entity_type' => 'persons',
        'code' => 'organization_id'
    ]);

    $personAttributes = app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
        'entity_type' => 'persons',
        'quick_add' => 1,
    ]);

    $orgAttrForPerson = app('Webkul\Attribute\Repositories\AttributeRepository')->findOneWhere([
        'entity_type' => 'persons',
        'code' => 'organization_id'
    ]);

    if ($orgAttrForPerson && ! $personAttributes->contains('id', $orgAttrForPerson->id)) {
        $personAttributes->push($orgAttrForPerson);
    }

    if ($organizationAttribute) {
        $organizationAttribute = clone $organizationAttribute;
        $organizationAttribute->code = 'person[' . $organizationAttribute->code . ']';
    }
@endphp

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-contact-component-template"
    >
        <div class="flex flex-col gap-4">
            <!-- Person Organization -->
            <x-admin::form.control-group class="relative z-20">
                <div class="flex items-center justify-between mb-1">
                    <x-admin::form.control-group.label class="!mb-0">
                        @lang('admin::app.leads.common.contact.organization')
                    </x-admin::form.control-group.label>

                    @if ($canCreateOrganization)
                        <button
                            type="button"
                            v-if="! person?.id"
                            class="text-xs font-semibold text-brandColor hover:underline dark:text-brandColor"
                            @click="openOrganizationModal"
                        >
                            + @lang('admin::app.contacts.organizations.index.create-btn')
                        </button>
                    @endif
                </div>

                <x-admin::attributes.edit.lookup />

                <v-lookup-component
                    :key="person.organization?.id ?? (organizationName ? 'new-' + organizationName : 'empty-org')"
                    :attribute='@json($organizationAttribute)'
                    :value="person.organization"
                    :is-disabled="person?.id ? true : false"
                    can-add-new="true"
                    @lookup-added="onOrganizationAdded"
                    @lookup-removed="onOrganizationRemoved"
                ></v-lookup-component>

                <template v-if="organizationName">
                    <x-admin::form.control-group.control
                        type="hidden"
                        name="person[organization_name]"
                        v-model="organizationName"
                    />
                </template>
            </x-admin::form.control-group>

            <!-- Person Search Lookup -->
            <x-admin::form.control-group class="relative z-10">
                <div class="flex items-center justify-between mb-1">
                    <x-admin::form.control-group.label class="required !mb-0">
                        @lang('admin::app.leads.common.contact.name')
                    </x-admin::form.control-group.label>

                    @if ($canCreatePerson)
                        <button
                            type="button"
                            class="text-xs font-semibold text-brandColor hover:underline dark:text-brandColor"
                            @click="openPersonModal"
                        >
                            + @lang('admin::app.contacts.persons.index.create-btn')
                        </button>
                    @endif
                </div>

                <x-admin::lookup
                    ::key="person?.organization?.id ?? 'all-persons'"
                    ::src="src"
                    name="person[id]"
                    ::params="params"
                    ::rules="nameValidationRule"
                    :label="trans('admin::app.leads.common.contact.name')"
                    ::value="{id: person.id, name: person.name}"
                    :placeholder="trans('admin::app.leads.common.contact.name-search-placeholder')"
                    @on-selected="addPerson"
                    :can-add-new="true"
                    ::search-keys="['name', 'emails', 'contact_numbers']"
                />

                <x-admin::form.control-group.control
                    type="hidden"
                    name="person[name]"
                    v-model="person.name"
                    v-if="person.name"
                />

                <x-admin::form.control-group.error control-name="person[id]" />
            </x-admin::form.control-group>

            <!-- Person Email -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('admin::app.leads.common.contact.email')
                </x-admin::form.control-group.label>

                <x-admin::attributes.edit.email />

                <v-email-component
                    :attribute="{'id': person?.id, 'code': 'person[emails]', 'name': 'Email'}"
                    validations="required"
                    :value="person.emails"
                    :is-disabled="person?.id ? true : false"
                ></v-email-component>
            </x-admin::form.control-group>

            <!-- Person Contact Numbers -->
            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('admin::app.leads.common.contact.contact-number')
                </x-admin::form.control-group.label>

                <x-admin::attributes.edit.phone />

                <v-phone-component
                    :attribute="{'id': person?.id, 'code': 'person[contact_numbers]', 'name': 'Contact Numbers'}"
                    :value="person.contact_numbers"
                    :is-disabled="person?.id ? true : false"
                ></v-phone-component>
            </x-admin::form.control-group>

            @if ($canCreatePerson)
                <!-- Quick Create Person Modal -->
                <Teleport to="body">
                    <x-admin::modal
                        ref="personModal"
                        size="large"
                    >
                        <x-slot:header>
                            <div class="flex items-center justify-between">
                                <p class="text-xl font-semibold text-gray-800 dark:text-white">
                                    @lang('admin::app.contacts.persons.create.title')
                                </p>
                            </div>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::form
                                v-slot="{ meta, errors, handleSubmit }"
                                as="div"
                                ref="personFormWrapper"
                            >
                                <form
                                    @submit="handleSubmit($event, createPerson)"
                                    ref="personForm"
                                >
                                    <input type="hidden" name="quick_add" value="person" />
                                    <input type="hidden" name="entity_type" value="persons" />

                                    <div class="grid gap-4 max-sm:flex-wrap pb-40">
                                        <x-admin::attributes
                                            :custom-attributes="$personAttributes"
                                        />
                                    </div>
                                </form>
                            </x-admin::form>
                        </x-slot>

                        <x-slot:footer>
                            <x-admin::button
                                class="primary-button"
                                :title="trans('admin::app.contacts.persons.create.save-btn')"
                                ::loading="isStoringPerson"
                                ::disabled="isStoringPerson"
                                @click="submitPersonForm"
                            />
                        </x-slot>
                    </x-admin::modal>
                </Teleport>
            @endif

            @if ($canCreateOrganization)
                <!-- Quick Create Organization Modal -->
                <Teleport to="body">
                    <x-admin::modal
                        ref="organizationModal"
                        size="large"
                    >
                        <x-slot:header>
                            <div class="flex items-center justify-between">
                                <p class="text-xl font-semibold text-gray-800 dark:text-white">
                                    @lang('admin::app.contacts.organizations.create.title')
                                </p>
                            </div>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::form
                                v-slot="{ meta, errors, handleSubmit }"
                                as="div"
                                ref="organizationFormWrapper"
                            >
                                <form
                                    @submit="handleSubmit($event, createOrganization)"
                                    ref="organizationForm"
                                >
                                    <input type="hidden" name="quick_add" value="organization" />
                                    <input type="hidden" name="entity_type" value="organizations" />

                                    <div class="grid gap-4 max-sm:flex-wrap">
                                        <x-admin::attributes
                                            :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                                'entity_type' => 'organizations',
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
                                :title="trans('admin::app.contacts.organizations.create.save-btn')"
                                ::loading="isStoringOrganization"
                                ::disabled="isStoringOrganization"
                                @click="submitOrganizationForm"
                            />
                        </x-slot>
                    </x-admin::modal>
                </Teleport>
            @endif
        </div>
    </script>

    <script type="module">
        app.component('v-contact-component', {
            template: '#v-contact-component-template',

            props: ['data'],

            data () {
                return {
                    is_searching: false,

                    person: this.data ? this.data : {
                        'name': ''
                    },

                    organizationName: null,

                    persons: [],

                    isStoringPerson: false,

                    isStoringOrganization: false,
                }
            },

            computed: {
                src() {
                    return "{{ route('admin.contacts.persons.search') }}";
                },

                params() {
                    const params = {
                        query: this.person['name'] || ''
                    };

                    if (this.person?.organization?.id) {
                        params.organization_id = this.person.organization.id;
                    }

                    return params;
                },

                nameValidationRule() {
                    return this.person.name ? '' : 'required';
                }
            },

            mounted() {
                if (this.person?.organization?.name && ! this.person?.organization?.id) {
                    this.organizationName = this.person.organization.name;
                }
            },

            methods: {
                addPerson (person) {
                    this.person = person || { name: '' };

                    if (this.person.id) {
                        this.organizationName = null;
                    } else if (this.person.organization?.name && ! this.person.organization?.id) {
                        this.organizationName = this.person.organization.name;
                    } else {
                        this.organizationName = null;
                    }
                },

                onOrganizationAdded(org) {
                    if (! this.person) {
                        this.person = { name: '' };
                    }

                    if (this.person.id && this.person.organization?.id !== org?.id) {
                        this.person = {
                            name: '',
                            emails: [{ value: '', label: 'work' }],
                            contact_numbers: [{ value: '', label: 'work' }],
                        };
                    }

                    this.person.organization = org;
                    this.organizationName = (! org?.id && org?.name) ? org.name : null;
                },

                onOrganizationRemoved() {
                    if (this.person) {
                        this.person.organization = null;
                    }

                    this.organizationName = null;
                },

                openPersonModal() {
                    if (this.$refs.personModal) {
                        this.$refs.personModal.open();
                    }
                },

                submitPersonForm() {
                    const form = this.$refs.personForm;

                    if (! form) {
                        return;
                    }

                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                    }
                },

                createPerson(params, { setErrors }) {
                    const form = this.$refs.personForm;

                    if (! form) {
                        return;
                    }

                    this.isStoringPerson = true;

                    const formData = new FormData(form);

                    this.$axios.post("{{ route('admin.contacts.persons.store') }}", formData)
                        .then(response => {
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message
                            });

                            if (this.$refs.personModal) {
                                this.$refs.personModal.close();
                            }

                            form.reset();

                            if (response.data.data) {
                                this.addPerson(response.data.data);
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
                            this.isStoringPerson = false;
                        });
                },

                openOrganizationModal() {
                    if (this.$refs.organizationModal) {
                        this.$refs.organizationModal.open();
                    }
                },

                submitOrganizationForm() {
                    const form = this.$refs.organizationForm;

                    if (! form) {
                        return;
                    }

                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                    }
                },

                createOrganization(params, { setErrors }) {
                    const form = this.$refs.organizationForm;

                    if (! form) {
                        return;
                    }

                    this.isStoringOrganization = true;

                    const formData = new FormData(form);

                    this.$axios.post("{{ route('admin.contacts.organizations.store') }}", formData)
                        .then(response => {
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message
                            });

                            if (this.$refs.organizationModal) {
                                this.$refs.organizationModal.close();
                            }

                            form.reset();

                            if (response.data.data) {
                                this.onOrganizationAdded(response.data.data);
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
                            this.isStoringOrganization = false;
                        });
                },
            }
        });
    </script>
@endPushOnce