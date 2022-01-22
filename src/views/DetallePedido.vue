<template>
  <div>
    <b-row>
      <b-col cols="12" v-if="mesa">
        <b-card v-if="pedido && pedido != null" :header="cardHeader" tag="article" class="m-3 mt-3" border-variant="primary" header-bg-variant="primary">
          <b-row class="mt-1 mb-2">
            <b-col cols="12">
              <b-alert v-if="isFE" show variant="info">Se creó una factura electrónica de este pedido, ya no se puede modificar.</b-alert>
              <b-form-group>
                <b-btn
                    v-if="pedido && pedido.editablepedido == 'NO' && pedido.idestado == '5' && pedido.descripcionRolSesion != 'MESERO'"
                    class="ml-3 mb-3 float-right"
                    @click.stop="marcarComoPago()"
                    variant="primary">Marcar como pago</b-btn>
                <b-btn
                    v-if="pedido && pedido.editablepedido == 'SI'"
                    class="ml-3 mb-3 float-right"
                    :disabled="isFE"
                    @click.stop="anularPedido()"
                    variant="primary">Anular Pedido</b-btn>
                <b-btn
                  v-if="pedido && pedido.editablepedido == 'SI'"
                  class="ml-3 mb-3 float-right"
                  :disabled="isFE"
                  @click.stop="abrirModalCambiarMesa()"
                  variant="primary">Cambiar de Mesa</b-btn>
                  <b-btn
                    v-if="(pedido && pedido.editablepedido == 'SI') || (pedido.descripcionestado == 'FACTURADO' && pedido.descripcionRolSesion != 'MESERO')"
                    class="ml-3 mb-3 float-right"
                    :disabled="isFE"
                    @click.stop="cargarFormulario(null,'Agregar')"
                    variant="primary">Agregar Producto</b-btn>         
                <b-btn
                  v-if="(pedido && pedido.editablepedido == 'SI') && (items && items.length > 0)"
                  class="ml-3 mb-3 float-right"
                  @click.stop="enviarPedido()"
                  variant="primary"><template v-if="(pedido && pedido.idestado > 1)"> Volver a </template>Enviar Pedido</b-btn>
                <b-btn
                  v-if="(pedido && pedido.descripcionestado == 'FACTURADO') || ((pedido && pedido.editablepedido == 'SI') && (items && items.length > 0) && (pedido && pedido.idestado > 1))"
                  class="ml-3 mb-3 float-right"
                  :disabled="!disableBtnFE || isFE"
                  @click.stop="facturarPedido()"
                  variant="primary">Generar Factura</b-btn>
                <b-btn
                  v-if="(pedido && pedido.descripcionestado == 'FACTURADO') || ((pedido && pedido.editablepedido == 'SI') && (items && items.length > 0) && (pedido && pedido.idestado > 1))"
                  class="ml-3 mb-3 float-right"
                  :disabled="disableBtnFE"
                  @click.stop="facturarFEPedido()"
                  variant="primary">FE</b-btn>
              </b-form-group>
            </b-col>
          </b-row>

          <b-row align-h="center" v-if="(pedido && pedido.descripcionestado == 'FACTURADO') || ((pedido && pedido.editablepedido == 'SI') && (items && items.length > 0) && (pedido && pedido.idestado > 1))">
            <b-col class="contenedor-tabla">
              
              <b-form-checkbox
                  id="checkbox-0"
                  v-model="pedido.checkFe"
                  name="checkbox-0"
                  :value="true"
                  :unchecked-value="false"
                  :disabled="isFE"
                  class="mb-3"
                >  ¿Desea generar factura electrónica?
                </b-form-checkbox>

              <b-card
                title=""
                tag="article"
                class="mb-4"
                v-if="pedido.checkFe">
                <b-container>
                  <b-row>
                    <b-col>
                      <b-form-group
                        label="Tipo de persona">
                        <b-form-select v-if="pedido" v-model="clienteFE.kindOfPerson" class="mb-3">
                          <option value="LEGAL_ENTITY">Persona Jurídica</option>
                          <option value="PERSON_ENTITY">Persona Natural</option>
                        </b-form-select>
                      </b-form-group>
                    </b-col>
                    <b-col>
                      <b-form-group
                        label="Tipo de identificacion">
                        <b-form-select v-if="pedido" :options="tiposDocumento" v-model="clienteFE.identificationObject.type" class="mb-3">
                        </b-form-select>
                      </b-form-group>
                    </b-col>
                    <b-col>
                      <b-form-group
                        description="Si es NIT, no es necesario poner el DV."
                        label="Número de identificación">
                        
                        <b-input-group class="">
                          <b-form-input v-model="clienteFE.identificationObject.number" trim></b-form-input>
                          <b-input-group-append>
                            <b-button variant="primary" :disabled="clienteFE.identificationObject.number.trim()==''" @click="getClientAlegra()">Buscar</b-button>
                          </b-input-group-append>
                        </b-input-group>
                      </b-form-group>
                    </b-col>
                  </b-row>

                  <b-row v-if="clienteFE.kindOfPerson == 'PERSON_ENTITY'">
                    <b-col>
                      <b-form-group
                        label="Primer nombre">
                        <b-form-input v-model="clienteFE.name.firstName" trim></b-form-input>
                      </b-form-group>
                    </b-col>
                    <b-col>
                      <b-form-group
                        label="Segundo nombre">
                        <b-form-input v-model="clienteFE.name.secondName" trim></b-form-input>
                      </b-form-group>
                    </b-col>
                    <b-col>
                      <b-form-group
                        label="Apellidos">
                        <b-form-input v-model="clienteFE.name.lastName" trim></b-form-input>
                      </b-form-group>
                    </b-col>
                  </b-row>

                  <b-row v-else>
                    <b-col>
                      <b-form-group
                        label="Razón social / nombre completo">
                        <b-form-input v-model="clienteFE.name" trim></b-form-input>
                      </b-form-group>
                    </b-col>
                  </b-row>

                  <b-row>
                    <b-col>
                      <b-form-group
                        label="Municipio">
                         <b-form-select v-if="pedido" :options="municipios" v-model="clienteFE.address.city" class="mb-3">
                        </b-form-select>
                      </b-form-group>
                    </b-col>
                    <b-col>
                      <b-form-group
                        label="Dirección">
                        <b-form-input v-model="clienteFE.address.address" trim></b-form-input>
                      </b-form-group>
                    </b-col>
                    <b-col>
                      <b-form-group
                        label="Correo electrónico">
                        <b-form-input v-model="clienteFE.email" type="email" trim></b-form-input>
                      </b-form-group>
                    </b-col>
                  </b-row>
                  
                  
                </b-container>
              </b-card>
            </b-col>
          </b-row>

          <b-row align-h="center">
            <b-col class="contenedor-tabla">
              
              <b-form-checkbox
                  id="checkbox-1"
                  v-model="pedido.facturar"
                  name="checkbox-1"
                  value="SI"
                  unchecked-value="NO"
                  :disabled="isFE"
                  class="mb-3"
                >  ¿Desea facturar este pedido?
                </b-form-checkbox>

              <b-card
                title=""
                tag="article"
                class="mb-2"
              >
                <b-container>

                  <label>Seleccione el tipo de pago:</label>
                  <b-form-select v-if="pedido" v-model="pedido.tipopago" class="mb-3" :disabled="isFE">
                    <option value="EFECTIVO">EFECTIVO</option>
                    <option value="TARJETA">TARJETA</option>
                  </b-form-select>
                  
                  <b-form-checkbox
                    id="checkbox-2"
                    v-model="asociarCliente"
                    name="checkbox-2"
                    value="true"
                    unchecked-value="false"
                    :disabled="isFE"
                    class="mb-3"
                  >
                    ¿Desea asociar un cliente a este pedido?
                  </b-form-checkbox>
                  <template v-if="asociarCliente == 'true'">
                    <b-row class="mb-3">
                      <b-col
                        class="text-left"
                        sm="6">
                        <span class="font-weight-bold">
                          <span style="color:red;">* </span>Telefono
                        </span>:<br>
                        <b-form-input
                          type="number"
                          required
                          v-model="pedido.telefonocliente"
                          class="form-control"/>
                      </b-col>

                      <b-col
                        sm="6"
                        class="text-left sm-6">
                        <span class="font-weight-bold">
                          <span style="color:red;">* </span>Nombre
                        </span>:<br>
                        <b-form-input
                          type="text"
                          required
                          v-model="pedido.nombrecliente"
                          class="form-control"/>
                      </b-col>

                      <b-col
                        sm="12"
                        class="text-left sm-12">
                        <span class="font-weight-bold">
                          <span style="color:red;">* </span>Dirección
                        </span>:<br>
                        <b-form-textarea
                          id="textarea"
                          v-model="pedido.direccioncliente"
                          rows="3"
                          max-rows="6"
                        ></b-form-textarea>
                      </b-col>
                    </b-row>
                  </template>
                </b-container>

                <b-btn
                  class="ml-3 mb-3 float-right"
                  @click.stop="guardarCliente"
                  :disabled="isFE"
                  variant="primary">Guardar Datos</b-btn>
              </b-card>

              <b-table
                v-if="items && items.length > 0"
                stacked="sm"
                striped
                hover
                class="columna-centrada"
                tbody-class="columna-centrada"
                :fields="campos"
                :items="items">
                <template slot="acciones" slot-scope="row">
                  <b-button
                    style="margin: 1px;"
                    class="ml-2"
                    @click.stop="cargarFormulario(row.item,'Ver')">
                    <i class="icon-eye"></i>
                  </b-button>
                  <b-button
                    v-if="(pedido && pedido.editablepedido == 'SI') || (pedido.descripcionestado == 'FACTURADO' && pedido.descripcionRolSesion != 'MESERO')"
                    style="margin: 1px;"
                    class="ml-2"
                    variant="primary"
                    :disabled="isFE"
                    @click.stop="cargarFormulario(row.item,'Modificar')">
                    <i class="icon-pencil"></i>
                  </b-button>
                  <b-button
                    v-if="(pedido && pedido.editablepedido == 'SI') || (pedido.descripcionestado == 'FACTURADO' && pedido.descripcionRolSesion != 'MESERO')"
                    style="margin: 1px;"
                    class="ml-2"
                    variant="danger"
                    :disabled="isFE"
                    @click.stop="cargarFormulario(row.item,'Eliminar')">
                    <i class="fa fa-trash"></i>
                  </b-button>
                </template>
              </b-table>
              <b-row v-if="items && items.length > 0" class="mt-1 mb-2 text-center">
                <b-col cols="12">
                  <b-alert show><strong>TOTAL: {{totalPedido()}}</strong></b-alert>
                </b-col>
              </b-row>
              <b-row v-else class="mt-1 mb-2 text-center">
                <b-col cols="12">
                  <b-alert show>El pedido está creado, pero no se encontraron registros de productos pedidos. Haz click en el boton Nuevo para agregar productos.</b-alert>
                  <b-btn
                    class="ml-3 float-right"
                    @click.stop="anularPedido()"
                    variant="primary">Anular Pedido</b-btn>
                </b-col>
              </b-row>
            </b-col>
          </b-row>

        </b-card>
        <b-card v-else :header="cardHeader" tag="article" class="m-3 mt-3" border-variant="primary" header-bg-variant="primary">
          <b-row class="mt-1 mb-2 text-center">
            <b-col cols="12">
              <b-alert show variant="info">No ha creado el pedido. Haz click en el boton Crear Pedido para iniciar.</b-alert>
               <b-btn
                class="ml-3 float-right"
                @click.stop="crearPedido()"
                variant="primary"
              >Crear Pedido</b-btn>
            </b-col>
          </b-row>
         
        </b-card>  
      </b-col>
      <b-col cols="12" v-else>
        <b-card tag="article" class="m-3 mt-3">
          <b-row class="mt-1 mb-2">
            <b-col cols="12">
              <b-alert show variant="danger">No ha seleccionado mesa.</b-alert>
            </b-col>
          </b-row>

        </b-card>
      </b-col>
    </b-row>

    <b-modal v-if="objeto" centered v-model="showModal" :title="tipoOperacion">
      <b-container>
        <b-row class="mb-3">
          <b-col
            class="text-left">
            <span class="font-weight-bold">
              <span style="color:red;"> </span>Tipo Producto
            </span>:<br>
            <span v-if="(tipoOperacion === 'Ver' || tipoOperacion === 'Eliminar')">
              {{objeto.descripciontipoproducto}}
            </span>
            <b-form-select v-else v-model="objeto.idtipoproducto" class="mb-3">
              <option :key="'tipo_' + t" v-for="(tipo, t) in tipoProductos" :value="tipo.id">{{tipo.descripcion}}</option>
            </b-form-select>
          </b-col>
        </b-row>
        <b-row class="mb-3">
          <b-col
            class="text-left">
            <span class="font-weight-bold">
              <span style="color:red;">* </span>Producto
            </span>:<br>
            <span v-if="(tipoOperacion === 'Ver' || tipoOperacion === 'Eliminar')">
              {{objeto.descripcionproducto}}
            </span>
            <b-form-select v-else v-model="objeto.idproducto" class="mb-3">
              <template v-for="(tipo, t) in tipoProductos">
                
                  <template v-if="objeto && objeto.idtipoproducto">
                    <template v-for="(producto, p) in tipo.productos">
                      <option v-if="producto.idtipoproducto == objeto.idtipoproducto" :key="'prod_' + t + '_' + p" :value="producto.id">{{producto.descripcion}}</option>
                    </template>
                  </template>
                  <template v-else>
                    <template v-for="(producto, p) in tipo.productos">
                      <option :key="'prod_' + t + '_' + p" :value="producto.id">{{producto.descripcion}}</option>
                    </template>
                  </template>
                
              </template>
            </b-form-select>
          </b-col>
        </b-row>
        <b-row class="mb-3">
          <b-col
            class="text-left">
            <span class="font-weight-bold">
              <span style="color:red;">* </span>Cantidad
            </span>:<br>
            <span v-if="(tipoOperacion === 'Ver' || tipoOperacion === 'Eliminar')">
              {{objeto.cantidadproducto}}
            </span>
            <b-form-input
              v-else
              type="number"
              required
              v-model="objeto.cantidadproducto"
              class="form-control"/>
          </b-col>
        </b-row>
        <b-row class="mb-3">
          <b-col
            class="text-left">
            <span class="font-weight-bold">
              <span style="color:red;"> </span>Observación
            </span>:<br>
            <span v-if="(tipoOperacion === 'Ver' || tipoOperacion === 'Eliminar')">
              {{objeto.descripcion}}
            </span>
            <b-form-input
              v-else
              type="text"
              required
              v-model="objeto.descripcion"
              class="form-control"/>
          </b-col>
        </b-row>
      </b-container>
      <div slot="modal-footer" v-if="objeto !== null" class="pull-right">
        <b-btn
          v-if="tipoOperacion === 'Ver'"
          size="sm"
          variant="secondary"
          @click.stop="cerrarFormulario">Cerrar</b-btn>
        <b-btn
          v-if="tipoOperacion === 'Agregar'"
          size="sm"
          variant="primary"
          @click.stop="guardar">Guardar</b-btn>
        <b-btn
          v-if="tipoOperacion === 'Modificar'"
          size="sm"
          variant="primary"
          @click.stop="guardarCambios">Guardar Cambios</b-btn>
        <b-btn
          v-if="tipoOperacion === 'Eliminar'"
          size="sm"
          variant="danger"
          @click.stop="eliminar">Eliminar</b-btn>
      </div>
    </b-modal>


    <b-modal centered v-model="showModalCambiarMesa" title="Cambiar de Mesa">
      <b-container>
        <b-row class="mb-3">
          <b-col class="text-left">
            <span class="font-weight-bold">
              <span style="color:red;"> </span>Seleccione la nueva mesa
            </span>:<br>
            <b-form-select  v-model="idMesaNueva" class="mb-3">
              <template v-for="(mesa, m) in mesasDisponibles" >
                <option :key="'mesa_' + m" :value="mesa.id" v-if="mesa.descripcionestadopedido == null">{{mesa.descripcion}}</option>
              </template>
            </b-form-select>
          </b-col>
        </b-row>
      </b-container>
      <div slot="modal-footer" class="pull-right">
        <b-btn
          variant="primary"
          @click.stop="cambiarMesa">Cambiar de Mesa</b-btn>
      </div>
    </b-modal>

  </div>
</template>
<style scoped>
.columna-centrada {
  text-align: center;
}
.mesa {
  cursor: pointer;
}
.border-td {
  border: 1px solid #000;
  padding: 5px;
}
</style>

<script>
var self = this;
export default {
  name: "DetallePedido",
  data: function() {
    return {
      pedido: null,
      mesa: this.$route.params.mesa ? this.$route.params.mesa : null,
      idPedido: this.$route.params.idPedido ? this.$route.params.idPedido : null,
      cardHeader: '<strong> Detalles del pedido, ' + (this.$route.params.mesa && this.$route.params.mesa.descripcion ? this.$route.params.mesa.descripcion : '') + ' ( ' + (this.pedido && this.pedido.descripcionestado ? this.pedido.descripcionestado : '') + ' )' + ' ( ' + (this.pedido && this.pedido.prefijofactura ? this.pedido.prefijofactura : '') + '-' + (this.pedido && this.pedido.numerofactura ? this.pedido.numerofactura : '') + ' )' + ' </strong>',
      showModal: false,
      showModalCambiarMesa: false,
      tipoProductos: [],
      campos: [
        {
          key: "cantidadproducto",
          label: "Cantidad",
          sortable: true,
          thStyle: "text-align:center;",
          tdClass: "columna-centrada"
        },
        {
          key: "descripcionproducto",
          label: "Producto",
          sortable: true,
          thStyle: "text-align:center;"
        },
        {
          key: "precioproducto",
          label: "Precio U.",
          sortable: true,
          thStyle: "text-align:center;",
          tdClass: "columna-centrada"
        },
        {
          key: "acciones",
          label: "Acciones",
          sortable: false,
          thStyle: "text-align:center;",
          tdClass: "columna-centrada"
        }
      ],
      items: [],
      objeto: null,
      objetoViejo: null,
      cantidadProductoVieja: 0,
      idMesaNueva: null,
      mesasDisponibles: [],
      nombreCliente: null,
      asociarCliente: false,
      tiposDocumento: [],
      municipios: [],
      clienteFEBase: {
        name: '',
        identificationObject: {
          type: 'NIT',
          number: ''
        },
        address: {
          address: '',
          department: 'N. de Santander',
          city: 'Cúcuta',
          country: 'Colombia'
        },
        kindOfPerson: 'LEGAL_ENTITY',
        email: '',
        type: [
          'client'
        ]
      },
      clienteFE: {}
    };
  },
  computed: {
    disableBtnFE: function () {
      if (this.pedido.isFE == 'true') {
        return true;
      }
      if (this.pedido.checkFe == false) {
        return true;
      } else {
        if (this.clienteFE && !this.clienteFE.identificationObject) {
          return true;
        }
        if (this.clienteFE.identificationObject.number.trim() == '') {
          return true;
        }
        if (this.clienteFE.email.trim() == '') {
          return true;
        }
        if (this.clienteFE.address.address.trim() == '') {
          return true;
        }
        if (this.clienteFE.kindOfPerson == 'LEGAL_ENTITY') {
          if (this.clienteFE.name.trim() == '') {
            return true
          }
        } else {
          if (this.clienteFE.name.firstName.trim() == '' || this.clienteFE.name.secondName.trim() == '' || this.clienteFE.name.lastName.trim() == '') {
            return true
          }
        }
      }
      return false;
    },
    isFE: function () {
      return this.pedido.isFE ==  'true';
    },
  },
  watch: {
    asociarCliente: function (valor) {
      if (valor === 'false') {
        this.pedido.nombrecliente = '';
        this.pedido.telefonocliente = '';
        this.pedido.direccioncliente = '';
      }
    },
    'pedido.telefonocliente': function (valor) {
      if (valor.length > 5) {
        this.consultarClienteDelPedidoPorTelefono(valor);
      }
    },
    'objeto.idproducto': function (valor) {
      const tipoProducto = this.tipoProductos.find((tipoProducto) => {
        return tipoProducto.id == this.objeto.idtipoproducto;
      });
      if (tipoProducto && tipoProducto.productos) {
        const producto = tipoProducto.productos.find((producto) => {
          return producto.id == this.objeto.idproducto;
        });
        this.objeto.descripcionproducto = producto.descripcion;
      }
    },
    'clienteFE.address.city': function (valor) {
      const municipioSeleccionado = this.municipios.find((municipio) => {
        return municipio.city == valor;
      });
      if (municipioSeleccionado) {
        this.clienteFE.address.country = municipioSeleccionado.country;
        this.clienteFE.address.department = municipioSeleccionado.department;
      }
    },
    'clienteFE.kindOfPerson': function (valor) {
      if (valor != 'LEGAL_ENTITY') {
        this.clienteFE.name = {
          firstName: '',
          secondName: '',
          lastName: ''
        }
      } else {
        this.clienteFE.name = '';
      }
    },
    'clienteFE.nameObject': function (valor) {
      if (valor && valor.firstName) {
        this.clienteFE.name = valor;
      }
    }
  },
  methods: {
    atras: function() {
      this.$router.go(-1)
    },
    resetClient: function() {
      this.clienteFE = {
        ...this.clienteFEBase
      }
	  this.clienteFE.identificationObject.number = '';
	  this.clienteFEBase.identificationObject.number = '';
    },
    getClientAlegra: function() {
      this.$loader.open({ message: "Cargando ..." });
      var self = this;
      var frm = {
        identification: self.clienteFE.identificationObject.number
      };
      this.$http.get("ws/cliente/", { params: frm }).then(resp => {
        if (resp.data.identificationObject) {
          self.clienteFE = {...resp.data};
		  self.clienteFE.identification = null;
        } else {
          self.resetClient();
        }
        self.$loader.close();
      });
    },
    listarTiposDocumento: function() {
      this.$loader.open({ message: "Cargando ..." });
      var self = this;
      var frm = {};
      this.$http.get("ws/tipodocumento/", frm).then(resp => {
        self.tiposDocumento = resp.data;
        self.$loader.close();
      });
    },
    listarMunicipios: function() {
      this.$loader.open({ message: "Cargando ..." });
      var self = this;
      var frm = {};
      this.$http.get("ws/municipio/", frm).then(resp => {
        self.municipios = resp.data;
        self.$loader.close();
      });
    },
    listarTiposPedido: function() {
      this.$loader.open({ message: "Cargando ..." });
      var self = this;
      var frm = {};
      this.$http.post("ws/productotipoproducto/", frm).then(resp => {
        self.tipoProductos = resp.data.tiposProducto;
        self.$loader.close();
      });
    },
    abrirModal: function() {
      this.showModal = true
    },
    cerrarModal: function() {
      this.showModal = false
    },
    cargarFormulario: function(obj, operacion) {
      this.tipoOperacion = operacion;
      this.objeto = obj;
      this.objetoViejo = null;
      if (obj === null) {
        this.objeto = {
          id: null,
          idtipoproducto: null,
          descripciontipoproducto: null,
          idproducto: null,
          descripcionproducto: null,
          costoproducto: null,
          cantidadproducto: null,
          precioproducto: null,
          descripcion: null
        };
      } else {
        this.selectedVisble = obj.visible
        this.cantidadProductoVieja = obj.cantidadproducto;
        this.objeto = {
          id: obj.id,
          idtipoproducto: obj.idtipoproducto,
          descripciontipoproducto: obj.descripciontipoproducto,
          idproducto: obj.idproducto,
          descripcionproducto: obj.descripcionproducto,
          costoproducto: obj.costoproducto,
          cantidadproducto: obj.cantidadproducto,
          precioproducto: obj.precioproducto,
          descripcion: obj.descripcion
        };
        this.objetoViejo = {
          ...this.objeto
        }
      }
      this.showModal = true;
    },
    validarCampos: function() {
      if (!this.objeto.idproducto || this.objeto.idproducto < 1) {
        this.$toast.error("Debe seleccionar un producto.");
        return false;
      }
      if (!this.objeto.cantidadproducto || this.objeto.cantidadproducto < 1) {
        this.$toast.error("Debe de escribir la cantidad.");
        return false;
      }
      return true;
    },
    crearPedido: function() {
      var self = this;
      var token = window.localStorage.getItem("token");
      var frm = {
        idmesa: self.mesa.id,
        token: token
      };
      this.$alertify
        .confirmWithTitle(
          "Guardar",
          "Seguro que desea crear un nuevo pedido?",
          function() {
            self.$loader.open({ message: "Creando ..." });
            self.$http.post("ws/pedido/", frm).then(resp => {
              var respuesta = resp.data;
              self.idPedido = respuesta.id;
              self.consultarPedido(self.idPedido);
              self.$toast.success(resp.data.mensaje);
              self.$loader.close();
            }).catch(resp => {
              self.$loader.close();
              if (resp.data && resp.data.mensaje) {
                self.$toast.error(resp.data.mensaje);
              } else {
                self.$toast.error("No se pudo crear el pedido");
              }
            });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    consultarPedido: function(id) {
      var self = this;
      var token = window.localStorage.getItem("token");
      var frm = {
        params: {
          id: id,
          token: token
        }
      };
      self.$loader.open({ message: "Cargando ..." });
      self.$http.get("ws/pedido/", frm).then(resp => {
        var respuesta = resp.data;
        self.pedido = respuesta;
        self.cardHeader =  '<strong> Detalles del pedido, ' + (this.$route.params.mesa && this.$route.params.mesa.descripcion ? this.$route.params.mesa.descripcion : '') + ' ( ' + (this.pedido && this.pedido.descripcionestado ? this.pedido.descripcionestado : '') + ' )' + ' ( ' + (this.pedido && this.pedido.prefijofactura ? this.pedido.prefijofactura : '') + ' - ' + (this.pedido && this.pedido.numerofactura ? this.pedido.numerofactura : '') + ' )' + ' </strong>';
        self.$loader.close();
        if (self.pedido.telefonocliente && self.pedido.nombrecliente && self.pedido.direccioncliente) {
          self.asociarCliente = 'true';
        } else {
          self.asociarCliente = 'false';
        }
        self.listar();
      }).catch(resp => {
        self.$loader.close();
        if (resp.data && resp.data.mensaje) {
          self.$toast.error(resp.data.mensaje);
        } else {
          self.$toast.error("No se pudo consultar el pedido");
        }
      });
    },
    consultarClienteDelPedidoPorTelefono: function(telefono) {
      var self = this;
      var token = window.localStorage.getItem("token");
      var frm = {
        params: {
          telefono: telefono,
          token: token
        }
      };
      self.$loader.open({ message: "Cargando ..." });
      self.$http.get("ws/pedido/consultar_cliente_por_telefono.php", frm).then(resp => {
        var respuesta = resp.data;
        self.$loader.close();
        if (respuesta.telefonocliente && respuesta.nombrecliente && respuesta.direccioncliente) {
          self.pedido.telefonocliente = respuesta.telefonocliente;
          self.pedido.nombrecliente = respuesta.nombrecliente;
          self.pedido.direccioncliente = respuesta.direccioncliente;
        } else {
          self.pedido.nombrecliente = '';
          self.pedido.direccioncliente = '';
        }
      }).catch(resp => {
        self.$loader.close();
        self.pedido.nombrecliente = '';
        self.pedido.direccioncliente = '';
      });
    },
    anularPedido: function() {
      var self = this;
      var token = window.localStorage.getItem("token");
      var frm = {
        id: self.idPedido,
        token: token,
        idestado: "6",
        idmesa: self.pedido.idmesa,
        nombrecliente: self.pedido.nombrecliente,
        telefonocliente: self.pedido.telefonocliente,
        direccioncliente: self.pedido.direccioncliente,
        tipopago: self.pedido.tipopago,
        numerofactura: self.pedido.numerofactura,
        prefijofactura: self.pedido.prefijofactura
      };
      this.$alertify
        .confirmWithTitle(
          "Anular",
          "Seguro que desea anular el pedido?",
          function() {
            self.$loader.open({ message: "Anulando ..." });
            self.$http.put("ws/pedido/", frm).then(resp => {
              var respuesta = resp.data;
              self.$toast.success(resp.data.mensaje);
              self.$loader.close();
              self.atras();
            }).catch(resp => {
              self.$loader.close();
              if (resp.data && resp.data.mensaje) {
                self.$toast.error(resp.data.mensaje);
              } else {
                self.$toast.error("No se pudo anular el pedido");
              }
            });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    marcarComoPago: function() {
      var self = this;
      var token = window.localStorage.getItem("token");
      if (!self.validarGuardarCliente()) {
        return false;
      }
      var frm = {
        id: self.idPedido,
        token: token,
        idestado: "7",
        idmesa: self.pedido.idmesa,
        nombrecliente: self.pedido.nombrecliente,
        telefonocliente: self.pedido.telefonocliente,
        direccioncliente: self.pedido.direccioncliente,
        tipopago: self.pedido.tipopago,
        numerofactura: self.pedido.numerofactura,
        prefijofactura: self.pedido.prefijofactura
      };
      this.$alertify
        .confirmWithTitle(
          "Anular",
          "Seguro que desea marcar como pago el pedido?",
          function() {
            self.$loader.open({ message: "Guardando cambios ..." });
            self.$http.put("ws/pedido/", frm).then(resp => {
              var respuesta = resp.data;
              self.$toast.success(resp.data.mensaje);
              self.$loader.close();
              self.atras();
            }).catch(resp => {
              self.$loader.close();
              if (resp.data && resp.data.mensaje) {
                self.$toast.error(resp.data.mensaje);
              } else {
                self.$toast.error("No se pudo marcar como pago el pedido");
              }
            });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    abrirModalCambiarMesa: function() {
      this.listarMesasDisponibles()
      this.showModalCambiarMesa = true
    },
    generarTicketCambioMesa: function(frm) {
      var self = this;
      let token = window.localStorage.getItem("token");
      self.$loader.open({ message: "Cambiando de mesa ..." });
      self.$http.post("ws/ticket/cambio-mesa.php", frm).then(resp => {
        self.$loader.close();
      })
      .catch(resp => {
        self.$loader.close();
        self.$toast.error("error generando ticket de cambio de mesa");
      });
    },
    cambiarMesa: function() {
      var self = this;
      if (!self.idMesaNueva) {
        self.$toast.error("Debe de seleccionar la mesa nueva");
        return false;
      }
      if (!self.validarGuardarCliente()) {
        return false;
      }
      var token = window.localStorage.getItem("token");
      var frm = {
        id: self.idPedido,
        token: token,
        idestado: self.pedido.idestado,
        idmesa: self.idMesaNueva,
        idmesaVieja: self.pedido.idmesa,
        nombrecliente: self.pedido.nombrecliente,
        telefonocliente: self.pedido.telefonocliente,
        direccioncliente: self.pedido.direccioncliente,
        tipopago: self.pedido.tipopago,
        numerofactura: self.pedido.numerofactura,
        prefijofactura: self.pedido.prefijofactura
      };
      this.$alertify
        .confirmWithTitle(
          "Cambiar de Mesa",
          "Seguro que desea cambiar de mesa?",
          function() {
            self.$loader.open({ message: "Cambiando ..." });
            self.$http.put("ws/pedido/", frm).then(resp => {
              var respuesta = resp.data;
              self.generarTicketCambioMesa(frm);
              self.$toast.success(resp.data.mensaje);
              self.$loader.close();
              self.atras();
            }).catch(resp => {
              self.$loader.close();
              if (resp.data && resp.data.mensaje) {
                self.$toast.error(resp.data.mensaje);
              } else {
                self.$toast.error("No se pudo anular el pedido");
              }
            });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    enviarPedido: function() {
      var self = this;
      var token = window.localStorage.getItem("token");
      if (!self.validarGuardarCliente()) {
        return false;
      }
      var frm = {
        id: self.idPedido,
        token: token,
        idestado: "2",
        idmesa: self.pedido.idmesa,
        nombrecliente: self.pedido.nombrecliente,
        telefonocliente: self.pedido.telefonocliente,
        direccioncliente: self.pedido.direccioncliente,
        tipopago: self.pedido.tipopago,
        numerofactura: self.pedido.numerofactura,
        prefijofactura: self.pedido.prefijofactura
      };
      this.$alertify
        .confirmWithTitle(
          "Enviar",
          "Seguro que desea enviar el pedido?",
          function() {
            self.$loader.open({ message: "Enviando ..." });
            self.$http.put("ws/pedido/", frm).then(resp => {
              var respuesta = resp.data;
              self.$toast.success(resp.data.mensaje);
              self.$loader.close();
              self.generarTicketPedido();
              self.atras();
            }).catch(resp => {
              self.$loader.close();
              if (resp.data && resp.data.mensaje) {
                self.$toast.error(resp.data.mensaje);
              } else {
                self.$toast.error("No se pudo enviar el pedido");
              }
            });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    validarGuardarCliente: function () {
      let self = this;
      if (this.asociarCliente === 'true') {
        if (!self.pedido.telefonocliente) {
          self.$toast.error("Debe escribir el número de teléfono del cliente");
          return false;
        }
        if (!self.pedido.nombrecliente) {
          self.$toast.error("Debe escribir el nombre del cliente");
          return false;
        }
        if (!self.pedido.direccioncliente) {
          self.$toast.error("Debe escribir la dirección del cliente");
          return false;
        }
      }
      return true;
    },
    guardarCliente: function() {
      var self = this;
      var token = window.localStorage.getItem("token");
      if (!self.validarGuardarCliente()) {
        return false;
      }
      var frm = {
        id: self.idPedido,
        token: token,
        idestado: self.pedido.idestado,
        idmesa: self.pedido.idmesa,
        nombrecliente: self.pedido.nombrecliente,
        telefonocliente: self.pedido.telefonocliente,
        direccioncliente: self.pedido.direccioncliente,
        tipopago: self.pedido.tipopago,
        numerofactura: self.pedido.numerofactura,
        prefijofactura: self.pedido.prefijofactura
      };
      this.$alertify
        .confirmWithTitle(
          "Guardar",
          "Seguro que desea guardar el cliente al pedido?",
          function() {
            self.$loader.open({ message: "Guardando ..." });
            self.$http.put("ws/pedido/", frm).then(resp => {
              var respuesta = resp.data;
              self.$toast.success(resp.data.mensaje);
              self.$loader.close();
              self.consultarPedido(self.pedido.id);
            }).catch(resp => {
              self.$loader.close();
              if (resp.data && resp.data.mensaje) {
                self.$toast.error(resp.data.mensaje);
              } else {
                self.$toast.error("No se pudo guardar el cliente al pedido");
              }
            });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    facturarPedido: function() {
      var self = this;
      var token = window.localStorage.getItem("token");
      if (!self.validarGuardarCliente()) {
        return false;
      }
      var frm = {
        id: self.idPedido,
        token: token,
        idestado: "5",
        idmesa: self.pedido.idmesa,
        nombrecliente: self.pedido.nombrecliente,
        telefonocliente: self.pedido.telefonocliente,
        direccioncliente: self.pedido.direccioncliente,
        tipopago: self.pedido.tipopago,
        facturar: self.pedido.facturar,
        numerofactura: self.pedido.numerofactura,
        prefijofactura: self.pedido.prefijofactura
      };
      this.$alertify
        .confirmWithTitle(
          "Facturar",
          "Seguro que desea facturar el pedido?",
          function() {
            self.$loader.open({ message: "Facturando ..." });
            self.$http.put("ws/pedido/", frm).then(resp => {
              var respuesta = resp.data;
              self.$toast.success(resp.data.mensaje);
              self.generarTicketFactura();
              self.$loader.close();
              self.atras();
            }).catch(resp => {
              self.$loader.close();
              if (resp.data && resp.data.mensaje) {
                self.$toast.error(resp.data.mensaje);
              } else {
                self.$toast.error("No se pudo facturar el pedido");
              }
            });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    facturarFEPedido: function() {
      var self = this;
      var token = window.localStorage.getItem("token");
      
      var frm = {
        id: self.idPedido,
        token: token,
        idestado: "5",
        idmesa: self.pedido.idmesa,
        nombrecliente: self.pedido.nombrecliente,
        telefonocliente: self.pedido.telefonocliente,
        direccioncliente: self.pedido.direccioncliente,
        tipopago: self.pedido.tipopago,
        facturar: self.pedido.facturar,
        numerofactura: self.pedido.numerofactura,
        prefijofactura: self.pedido.prefijofactura,
        clienteFE: self.clienteFE
      };

      this.$alertify
        .confirmWithTitle(
          "Factura Electrónica",
          "Seguro que desea facturar electrónicamente el pedido?",
          function() {
            self.$loader.open({ message: "Facturando ..." });
            self.$http.put("ws/pedido/fe.php", frm).then(resp => {
              var respuesta = resp.data;
              self.$toast.success(resp.data.mensaje);
              self.$loader.close();
              self.atras();
            }).catch(resp => {
              self.$loader.close();
              if (resp.data && resp.data.mensaje) {
                self.$toast.error(resp.data.mensaje);
              } else {
                self.$toast.error("No se pudo facturar el pedido");
              }
            });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    listar: function() {
      this.$loader.open({ message: "Cargando ..." });
      var self = this;
      var frm = { params: {
          idpedido: self.pedido.id
        }
      };
      this.$http.get("ws/detallepedido/", frm).then(resp => {
          self.items = resp.data;
          if (self.items && self.items.length > 0) {
            self.totalFilas = self.items.length;
          }
          self.$loader.close();
        })
        .catch(resp => {
          self.$loader.close();
          if (resp.data && resp.data.mensaje) {
            self.$toast.error(resp.data.mensaje);
          } else {
            self.$toast.error("No se pudo obtener los items de detalles del pedido.");
          }
        });
    },
    guardar: function() {
      if(!this.validarCampos()) {
        return false
      }
      var self = this;
      self.$set(self.objeto, "token", window.localStorage.getItem("token"));
      self.$set(self.objeto, "idpedido", this.pedido.id);
      self.$set(self.objeto, "mesa", this.mesa);
      self.$set(self.objeto, "pedido", this.pedido);
      this.$alertify
        .confirmWithTitle(
          "Guardar",
          "Seguro que desea guardar el nuevo registro?",
          function() {
            self.$loader.open({ message: "Guardando ..." });
            self.$http.post("ws/detallepedido/", self.objeto).then(resp => {
                self.$loader.close();
                self.listar();
                self.$toast.success(resp.data.mensaje);
                self.showModal = false;
              })
              .catch(resp => {
                self.$loader.close();
                if (resp.data && resp.data.Error) {
                  self.$toast.error(resp.data.Error);
                } else {
                  self.$toast.error("error registrando");
                }
              });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    guardarCambios: function() {
      if(!this.validarCampos()) {
        return false
      }
      var self = this;
      self.$set(self.objeto, "token", window.localStorage.getItem("token"));
      self.$set(self.objeto, "idpedido", this.pedido.id);
      self.$set(self.objeto, "cantidadproductosumar", (parseInt(self.cantidadProductoVieja) - parseInt(self.objeto.cantidadproducto)));
      self.$set(self.objeto, "productoviejo", this.objetoViejo);
      self.$set(self.objeto, "mesa", this.mesa);
      self.$set(self.objeto, "pedido", this.pedido);
      this.$alertify
        .confirmWithTitle(
          "Modificar",
          "Seguro que desea modificar el registro?",
          function() {
            self.showModal = false;
            self.$loader.open({ message: "Guardando Cambios ..." });
            self.$http.put("ws/detallepedido/", self.objeto).then(resp => {
                self.$loader.close();
                self.listar();
                self.$toast.success(resp.data.mensaje);
              })
              .catch(resp => {
                self.$loader.close();
                self.showModal = true;
                if (resp.data && resp.data.mensaje) {
                  self.$toast.error(resp.data.mensaje);
                } else {
                  self.$toast.error("error modificando");
                }
              });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    eliminar: function() {
      var self = this;
      let token = window.localStorage.getItem("token");
      self.$set(self.objeto, "token", window.localStorage.getItem("token"));
      self.$set(self.objeto, "mesa", this.mesa);
      self.$set(self.objeto, "pedido", this.pedido);
      this.$alertify
        .confirmWithTitle(
          "Eliminar",
          "Seguro que desea eliminar el registro?",
          function() {
            self.showModal = false;
            self.$loader.open({ message: "Eliminando ..." });
            self.$http.delete("ws/detallepedido/", {params: {id: self.objeto.id, token: token}, data: self.objeto }).then(resp => {
                self.$loader.close();
                self.listar();
                self.$toast.success(resp.data.mensaje);
              })
              .catch(resp => {
                self.$loader.close();
                self.showModal = true;
                if (resp.data && resp.data.mensaje) {
                  self.$toast.error(resp.data.mensaje);
                } else {
                  self.$toast.error("error eliminando");
                }
              });
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    cerrarFormulario: function() {
      this.showModal = false;
    },
    actualizarTabla: function(itemsFiltrados) {
      // this.totalFilas = itemsFiltrados.length;
      // this.paginaActual = 1;
    },
    generarTicketPedido: function() {
      var self = this;
      let token = window.localStorage.getItem("token");
      var frm = { 
        productos: self.items,
        mesa: self.mesa,
        token: token,
        idmesero: self.pedido.idmesero
      }
      self.$loader.open({ message: "Generando ..." });
      self.$http.post("ws/ticket/pedido.php", frm).then(resp => {
          self.$loader.close();
          // self.$toast.success(resp.data.mensaje);
          // self.$toast.success("Exito");
        })
        .catch(resp => {
          self.$loader.close();
          self.$toast.error("error generando ticket de pedido");
        });
    },
    generarTicketFactura: function() {
      var self = this;
      var token = window.localStorage.getItem("token");
      var frm = { 
        productos: self.items,
        mesa: self.mesa,
        cliente: self.nombreCliente,
        mesero: self.pedido.mesero,
        id: self.idPedido,
        token: token,
        idmesa: self.pedido.idmesa,
        nombrecliente: self.pedido.nombrecliente,
        telefonocliente: self.pedido.telefonocliente,
        direccioncliente: self.pedido.direccioncliente,
        tipopago: self.pedido.tipopago,
        facturar: self.pedido.facturar,
        numerofactura: self.pedido.numerofactura,
        prefijofactura: self.pedido.prefijofactura
      }
      self.$loader.open({ message: "Generando ..." });
      self.$http.post("ws/ticket/factura.php", frm).then(resp => {
          self.$loader.close();
          // self.$toast.success(resp.data.mensaje);
          // self.$toast.success("Exito");
        })
        .catch(resp => {
          self.$loader.close();
          self.$toast.error("error generando ticket de factura");
        });
    },
    totalPedido: function() {
      var self = this;
      var total = 0;
      this.items.forEach(item => {
        total += (parseInt(item.precioproducto) * parseInt(item.cantidadproducto));
      });
      var total = '$' + Number(total.toFixed(1)).toLocaleString();
      return total;
    },
    listarMesasDisponibles: function() {
      this.$loader.open({ message: "Cargando ..." });
      var self = this;
      var frm = { params: {
        }
      };
      this.$http.get("ws/mesa/", frm).then(resp => {
          self.mesasDisponibles = resp.data;
          self.$loader.close();
        })
        .catch(resp => {
          self.$loader.close();
          if (resp.data && resp.data.mensaje) {
            self.$toast.error(resp.data.mensaje);
          } else {
            self.$toast.error("No se pudo obtener los items de las mesas disponibles.");
          }
        });
    },
  },
  created: function() {
    this.resetClient();
    if (this.idPedido) {
      this.consultarPedido(this.idPedido);
    }
    this.listarTiposPedido();
    this.listarTiposDocumento();
    this.listarMunicipios();
  },
  mounted: function() {
    this.$loader.close();
  }
};
</script>
