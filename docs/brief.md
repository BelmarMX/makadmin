# NECESIDAD

Construiremos un sistema para veterinarias multisucursal, el objetivo es tener orden administrativo, control de inventarios, punto de venta, control de pacientes (mascotas), proveedores y otros.. Nuestro primer acercamiento lo vamos a realizar con una sola veterinaria pero esto debe escalar rapidamente para soportar 30 o 100 veterinarias multiusuario disponibles para versión desktop y uso en tablets, vamos a partir de la estructura que Laravel 13 y Vue Starter Kit nos dan para empezar a construir el sistema.  
Este sistema deberá ser modular, es decir un super administrador permitirá que la clinica tenga o no activos modulos distintos, por lo que deberán trabajar por separado y en conjunto.

# REQUERIMIENTO

El sistema debe permitir la administración de la veterinaria, por ello debemos fundamentar las reglas de arquitectura, diseño, estilo, y lenguajes que nos aportarán beneficios al momento de escalar.  
El primer paso es crear los cimientos para que agentes de IA puedan seguir trabajando siguiendo el estilo de UI / Reglas de negocio / Modularidad / Arquitectura, etc.  
Posteriormente debemos crear un plan de tareas para que vayamos desarrollando módulo por módulo.  
Debemos aprovechar completamente la base que Laravel 13 y Vue Starter Kit ponen a nuestra disposición como el Dashboard inicial.  
Debemos estructurar las herramientas de seguridad, permisos y capas de dominio que nos permitan tener el control de una aplicación limpia, escalable, cenrtralizada y que permita incorporar nuevos servicios sin sufrir.  
Debe estar pensado para incluir servicios de almacenamiento en la nube aunque inicialmente el almacenamiento será en disco, conectar otros como whatsapp (Evolution API), google Calendar, y más servicios que nos permitan extender el funcionamiento, pero no debe estar “amarrado” a estos servicios ya que en cualquier momento podemos cambiar de proveedor y el comportamiento debe seguir siendo el mismo solo ajustando pocas herramientas.  
Cada veterinaria debe tener su propia configuración (módulos), administradores y usuarios, permisos que el administrador puede conceder / revocar y ninguna otra veterinaria debería poder ver ni por error información de otra.  
Un superadministrador (yo) podré tener acceso a todo: Ceder permisos a modulos / emular sesión de cualquier usuario / tener una versión demo con información sensible ofuscada / quitar o ceder permisos a usuarios / ver estadisticas de cada clínica / o clinica - sucursal  
Debemos estar preparados para eventos en tiempo real con websockets ya que tendremos una sala de espera / notificaciones / citas proximas / consultas ahora / turnos / etc.  
Cada módulo que se cree deberá tener sus propios permisos “ver, crear, editar, eliminar”  
Todo lo que se elimine deberá ser un borrado lógico, nada destructivo  
Todos los movimientos deben ser logueados, debe haber la opción de auditar que pasa en cada interacción del sistema. Un manejo inteligente de los eventos.  
Cada veterinaria deberá poder ingresar por un subdominio: inuvet.vetfollow.com por ejemplo  
Debemos seguir las recomendaciones de las Normas oficiales mexicanas para el manejo de medicamentos controlados  
Debemos estar preparados para audotirias estrictas, así que “nada debe perderse” (sobre todo los medicamentos controlados)  
Los usuarios deberán tener la oportunidad de cambiar su avatar, nombre, teléfono y contraseña (pero nunca su correo)  
El superadministrador es el único que puede dar de alta una nueva clínica, con su logo, nombre, datos fiscales y datos del medico responsable (con su cedula de identificación)

# ESTRUCTURA

De acuerdo al primer levantamiento con nuestra veterinaria de prueba vamos a establecer la distribución de los modulos de la siguiente forma:

## 1. Gestión Clínica y pacientes (Corazón del sistema)

- Expediente clínico universal: Historial completo, signos vitales, diagnostico.
- Módulo de consulta avanzado: Registro de pruebas de laboratorio y gabinete.
- Recetario inteligente: Ligada a productos de inventario
    - Receta Cuantificada: Específicamente para medicamentos controlados.
- Consentimiento y responsivas: Generación automática de documentos para cirugías y anestecia.
- Seguimiento post-consulta: CRM para recordatorios de segumiento, citas, notificaciones, etc.

## 2. Inventario con trazabilidad (Multi-Lote)

- Gestión por lotes y caducidades: El producto puede tener el mismo sku / nombre pero diferentes entradas (Lotes y fechas de caducidad)
- Alertas de proximidad: Sistema de semaforo basado en promedio de ventas para decidir si comprar productos de caducidad corta
- Alertas de caducidad: Notificaciones internas cuando algún medicamento esté proximo a vencer.
- Unidades de medida hibrida: Venta por caja, blister, unidad, mililitros
- Kárdex de movimientos: Trazabilidad total de quién sacó qué y por qué (ligado a folio de consulta)
- Predicción de compras: Algoritmo básico que analiza el stock mínimo vs velicidad de ventas para sugerir órdenes de compra por proveedor.
- Catálogo de productos / servicios y combos

## 3. Agenda de citas (Workflow) (Optimización del tiempo del personal)

- Citas encadenadas: Flujo de trabajo lógico (Estética -> Baño -> Consulta)
- Sincronización con google calendar (Para que el médico o usuario vea su agenda en el celular)
- Asignación de roles: Separación clara entre agenda de médicos, esteticistas, cirujanos.
- Gestión de Inasistencias (no-show): Estadísticas de clientes que faltan para implementar políticas de prepago / recordatorios mas agresivos

## 4. Punto de venta POS y Finanzas (Control total del flujo de efectivo)

- Caja y sucursales: Cortes de caja independientes por turno y sucursal
- Cuentas por cobrar (CXC): Manejo de abonos de clientes frecuentes, estados de cuenta claros, recordatorios de pago
- Abonos flexibles dirigidos a algún adeudo especifico o automático por antiguedad
- Pagos múltimo moneda / extranjeros: Distinción clara de cobro de comisiones por pagos con tarjetas extranjeras
- Combos dinámicos: Paquetes que descuentan varios insumos del inventario E.G. “Esterilización” descuenta Anestesia, gasa, sutura y honorarios.
- Siempre flexible (El usuario vendedor podrá editar conceptos / montos / descuentos / aunque el inventario tenga algún precio fijo)

## 5. Estética y hospitalización (Servicios especializados con sus propias reglas)

- Estética: Registro de tiempos por raza/paciente para optimizar la agenda y responsivas por estado de piel /pelo.
- Hospitalización: Monitor de sala de espera y tablero de pacientes internos con horas de medicación
- Comisiones: Cálculo automático de comisiones para esteticitas y ciurujanos, separando el “fee”. de la clínica.

## 6. Proveedores

- Directorio donde puedas comparar precios históticos de una misma medicina entre diferentes proveedores
- Ordenes de compra
- Listas de precios
- Adeudos y pagos

## 7. Automatización de notificaciones

- Recordatorios de citas
- Avisos automáticos (whatsapp / email / impresos) para vacunas, desparacitación, consultas

## 8. Dashboard inteligente de negocio

- Indices de utilidad: Analisis de servicios / productos que dejan más margen restando insumos
- Análisis de pacientes: Segmentación por especie
- Alertas críticas: Productos por caducar, cuentas por pagar vencidas, adeudos a prioveedores

## 9. Portal del cliente

- Una web simple donde los clientes puedan ver citas pendientes, vacunas, consultas, resultados, recetas (con tiempo de expiración), agendar citas
- Proximamente: Realizar abonos

# INTERFAZ

El diseño de la interfaz deberá seguir (por ahora) lo que nos ofrece VUE Starter KIT: Pero deberá mantener coherencia visual, verse premium, moderno, altamente tecnologico, fácil de usar, intuitivo, nada debería estar a mas de 3 clics de distancia, tema claro / oscuro

- Debe estar orientado al uso en equipos de escritorio (monitores medianos / grandes)
- La versión compacta para tablets
- La versión móvil deberá ser el último recurso a modo de audotoria pero también debe funcionar aquí.
- Botones grandes para usar en pantallas táctiles
- Iconos descriptivos
- Reactividad: Los cambios que vienen de actividades deben verse sin refrescar la pantalla

Apoyarnos de estas instrucciones:  
La paleta de colores dominante será un tema oscuro premium, con grises grafito (--background: 222 47% 6%;), azul petróleo (--primary: 217 91% 60%; y --accent: 199 89% 48%;) y acentos funcionales en verde y rojo técnico para indicadores de estado o rendimiento.  
Los botones deben tener un estilo amigable (nada filoso, redondeado discreto), casi o siempre apoyados de Iconos descriptivos.

# INFRAESTRUCTURA

- Laravel 13
- VUE Starter Kit con TS
- Inertia
- Fortify
- Wayfinder
- Postgre SQL
- Claude Code como agente de IA

# PLANEACIÓN

El resultado de todo este contenido debe ser una instrucción para claude code, debemos darle todo el contexto para que organice uno o varios archivos dentrod e una carpeta .task que se llamen implementation, con una lista de tareas que iremos definiendo.

Lo que quiero es que por ejemplo hoy avancemos con el modulo de clínicas: Para poder crear mi primer clínica, cargar la información siguiendo todas las reglas que quiero  
Mañana volver a decirle: continua con taks-2.md que será otro modulo.. y así siempre teniendo el contexto completo del objetivo aquí descrito, pero con un orden super claro de lo que estamos implementando.

Por último, también quiero que me entregues un resumen ejecutivo de lo que estaremos implementando y su alcance para poder mostrarselo a mi cliente, que analice si hizo falta algo y poder hacer ajustes en el camino.
