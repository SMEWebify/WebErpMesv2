'===========================================================
' RADQuote -> WEM export script (MySQL via ODBC)
' Refactor of YOUR provided version:
' - Parameterized SQL (no string concatenation / safer)
' - Single transaction (atomic export)
' - Cache methods_services (performance)
' - Guards for missing reference IDs (unit/vat/payment/etc.)
' - Keep RADQuote model usage as-is (QUOTE / Quote / PartLine / Operations)
' - Keep WEM column spelling seting_time
'===========================================================

Option Explicit On
Option Strict On

Imports System
Imports System.Data
Imports System.Data.Odbc
Imports System.Globalization
Imports System.Collections.Generic
Imports System.Linq

'-----------------------------------------------------------
' Variables from RADQuote Constants
'-----------------------------------------------------------
Dim ServerIp As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Serveur_IP", "localhost").Value.StringValue
Dim ServerPort As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Serveur_port", "3306").Value.StringValue
Dim DatabaseName As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Nom_base", "erp").Value.StringValue
Dim DatabaseUser As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Utilisateur", "").Value.StringValue
Dim DatabasePassword As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Mot_de_passe", "").Value.StringValue
Dim MethodsUnitCode As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Methods_Unit_Code", "").Value.StringValue
Dim VATCode As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_VAT_Code", "").Value.StringValue
Dim DeliveriesCode As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Deliveries_Code", "").Value.StringValue
Dim PaymentConditionsCode As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Payment_Conditions_Code", "").Value.StringValue
Dim PaymentMethodsCode As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Payment_Methods_Code", "").Value.StringValue

'-----------------------------------------------------------
' Database table names
'-----------------------------------------------------------
Const QuoteTable As String = "quotes"
Const QuoteLinesTable As String = "quote_lines"
Const QuoteLinesDetailsTable As String = "quote_line_details"
Const TasksTable As String = "tasks"
Const SubAssembliesTable As String = "sub_assemblies" 'kept for compatibility even if unused
Const UserTable As String = "users"
Const CompaniesTable As String = "companies"
Const CompaniesContactTable As String = "companies_contacts"
Const CompanieAddressesTable As String = "companies_addresses"
Const PaymentConditionsTable As String = "accounting_payment_conditions"
Const PaymentMethodsTable As String = "accounting_payment_methods"
Const DeliveriesTable As String = "accounting_deliveries"
Const MethodsServicesTable As String = "methods_services"
Const MethodsUnitsTable As String = "methods_units"
Const AccountingVatsTable As String = "accounting_vats"

'-----------------------------------------------------------
' Database column names
'-----------------------------------------------------------
Const QuoteUUID As String = "uuid"
Const QuoteCode As String = "code"
Const QuoteLabel As String = "label"
Const QuoteReference As String = "customer_reference"
Const QuoteCustomerId As String = "companies_id"
Const QuoteCustomerContactId As String = "companies_contacts_id"
Const QuoteCustomerAddressId As String = "companies_addresses_id"
Const QuoteValidityDate As String = "validity_date"
Const QuoteUserId As String = "user_id"
Const QuotePaymentConditionsId As String = "accounting_payment_conditions_id"
Const QuotePaymentMethodsId As String = "accounting_payment_methods_id"
Const QuoteDeliveriesId As String = "accounting_deliveries_id"
Const QuoteComment As String = "comment"
Const CreatedAt As String = "created_at"
Const UpdatedAt As String = "updated_at"

Const QuoteLineQuoteId As String = "quotes_id"
Const QuoteLineOrdre As String = "ordre"
Const QuoteLineCode As String = "code"
Const QuoteLineProductId As String = "product_id"  'kept (unused here)
Const QuoteLineLabel As String = "label"
Const QuoteLineQty As String = "qty"
Const QuoteLineMethodsUnitsId As String = "methods_units_id"
Const QuoteLineSellingPrice As String = "selling_price"
Const QuoteLineDiscount As String = "discount"      'kept (unused here)
Const QuoteLineAccountingVatsId As String = "accounting_vats_id"
Const QuoteLineDeliveryDate As String = "delivery_date" 'kept (unused here)

Const QuoteLineDetailsQuoteId As String = "quote_lines_id"
Const QuoteLineDetailsXsize As String = "x_size"
Const QuoteLineDetailsYsize As String = "y_size"
Const QuoteLineDetailsZsize As String = "z_size"
Const QuoteLineDetailsXoversizesize As String = "x_oversize" 'kept (unused here)
Const QuoteLineDetailsYoversizesize As String = "y_oversize" 'kept (unused here)
Const QuoteLineDetailsZoversizesize As String = "z_oversize" 'kept (unused here)
Const QuoteLineDetailsDiameter As String = "diameter"        'kept (unused here)
Const QuoteLineDetailsMaterial As String = "material"
Const QuoteLineDetailsThickness As String = "thickness"
Const QuoteLineDetailsWeight As String = "weight"
Const QuoteLineDetailsBendCount As String = "bend_count"     'kept (unused here)
Const QuoteLineDetailsMaterialLossRate As String = "material_loss_rate" 'kept (unused here)
Const QuoteLineDetailsCadFile As String = "cad_file"         'kept (unused here)
Const QuoteLineDetailsCamFile As String = "cam_file"         'kept (unused here)
Const QuoteLineDetailsCadFilePath As String = "cad_file_path" 'kept (unused here)
Const QuoteLineDetailsCamFilePath As String = "cam_file_path" 'kept (unused here)
Const QuoteLineDetailsPicture As String = "picture"          'kept (unused here)
Const QuoteLineDetailsComment As String = "internal_comment"

Const TaskLabel As String = "label"
Const TaskOrdre As String = "ordre"
Const TaskQuoteLineId As String = "quote_lines_id"
Const TaskServiceId As String = "methods_services_id"
Const TaskSettingTime As String = "seting_time" 'IMPORTANT: keep exact column name
Const TaskUnitTime As String = "unit_time"
Const TaskStatuId As String = "status_id"
Const TaskType As String = "type"
Const TaskQty As String = "qty"
Const TaskUnitCost As String = "unit_cost"
Const TaskUnitPrice As String = "unit_price"
Const TaskMethodsUnitsId As String = "methods_units_id"
Const TaskOrigin As String = "origin"

'-----------------------------------------------------------
' Connection + helpers
'-----------------------------------------------------------
Dim messages As New List(Of String)()

Dim Normalize As Func(Of String, String) =
    Function(s As String) As String
        If s Is Nothing Then Return ""
        Dim t As String = s.Trim()
        While t.Contains("  ")
            t = t.Replace("  ", " ")
        End While
        Return t
    End Function

Dim SafeText As Func(Of String, String) =
    Function(s As String) As String
        If String.IsNullOrWhiteSpace(s) Then Return ""
        Return s.Replace(Environment.NewLine, "|")
    End Function

Dim GetQuoteReference As Func(Of String) =
    Function() As String
        Dim detail As RadQuote.Configuration.ServerParameters.Details.Detail =
            QUOTE.Details.FirstOrDefault(Function(d) String.Equals(d.ExternalId, "REFERENCE", StringComparison.OrdinalIgnoreCase))
        If detail Is Nothing OrElse detail.Value Is Nothing Then Return ""
        Dim reference As String = detail.Value.Value
        If detail.IsRichComment Then reference = SafeText(reference)
        Return reference
    End Function

Dim GetQuoteComment As Func(Of String) =
    Function() As String
        Dim detail As RadQuote.Configuration.ServerParameters.Details.Detail =
            QUOTE.Details.FirstOrDefault(Function(d) String.Equals(d.ExternalId, "COMMENT", StringComparison.OrdinalIgnoreCase))
        If detail Is Nothing OrElse detail.Value Is Nothing Then Return ""
        Dim commment As String = detail.Value.Value
        If detail.IsRichComment Then commment = SafeText(commment)
        Return commment
    End Function

Dim GetNumberOfBends As Func(Of RadQuote.Business.Parts.SymbolPart, Integer) =
    Function(sym As RadQuote.Business.Parts.SymbolPart) As Integer
        If sym Is Nothing OrElse sym.Sym Is Nothing Then Return 0

        ' 1) Essai via QuotationInfo (si dispo)
        Try
            If sym.Sym.HasQuotationInfo(RADev.Radan.QuotationInfoNum.NumberOfBends) Then
                Dim q As Integer = sym.Sym.IntQuotationSingleValue(RADev.Radan.QuotationInfoNum.NumberOfBends)
                If q > 0 Then Return q
            End If
        Catch
            'ignore
        End Try

        ' 2) Sinon via attribut 500
        Try
            If sym.Sym.HasAttribute(500) Then
                Dim v As String = Convert.ToString(sym.Sym.GetAttribute(500).Value)
                If Not String.IsNullOrWhiteSpace(v) AndAlso v <> "0" Then
                    Dim n As Integer
                    If Integer.TryParse(v, n) AndAlso n > 0 Then Return n
                End If
            End If
        Catch
            'ignore
        End Try

        Return 0
    End Function


Dim ExecuteScalarInt As Func(Of OdbcConnection, OdbcTransaction, String, OdbcParameter(), Integer) =
    Function(cn As OdbcConnection, tx As OdbcTransaction, sql As String, ps As OdbcParameter()) As Integer
        Using cmd As New OdbcCommand(sql, cn, tx)
            If ps IsNot Nothing Then cmd.Parameters.AddRange(ps)
            Dim obj As Object = cmd.ExecuteScalar()
            If obj Is Nothing OrElse obj Is DBNull.Value Then Return 0
            Return Convert.ToInt32(obj)
        End Using
    End Function

Dim ExecuteNonQuery As Action(Of OdbcConnection, OdbcTransaction, String, OdbcParameter()) =
    Sub(cn As OdbcConnection, tx As OdbcTransaction, sql As String, ps As OdbcParameter())
        Using cmd As New OdbcCommand(sql, cn, tx)
            If ps IsNot Nothing Then cmd.Parameters.AddRange(ps)
            cmd.ExecuteNonQuery()
        End Using
    End Sub

Dim GetLastInsertId As Func(Of OdbcConnection, OdbcTransaction, Integer) =
    Function(cn As OdbcConnection, tx As OdbcTransaction) As Integer
        Return ExecuteScalarInt(cn, tx, "SELECT LAST_INSERT_ID()", Nothing)
    End Function

Dim GetIdByCode As Func(Of OdbcConnection, OdbcTransaction, String, String, Integer) =
    Function(cn As OdbcConnection, tx As OdbcTransaction, tableName As String, code As String) As Integer
        If String.IsNullOrWhiteSpace(code) Then Return 0
        Dim sql As String = "SELECT id FROM " & tableName & " WHERE code = ? LIMIT 1"
        Return ExecuteScalarInt(cn, tx, sql, New OdbcParameter() {
            New OdbcParameter("@p1", OdbcType.VarChar) With {.Value = code.Trim()}
        })
    End Function

'-----------------------------------------------------------
' Open connection
'-----------------------------------------------------------
Dim MyCon As New OdbcConnection()
MyCon.ConnectionString = "Driver={MySQL ODBC 8.4 ANSI Driver};DATABASE=" & DatabaseName & ";SERVER=" & ServerIp & ";PORT=" & ServerPort & ";UID=" & DatabaseUser & ";PASSWORD=" & DatabasePassword & ";"

MyCon.Open()

If MyCon.State = ConnectionState.Open Then

    Using tx As OdbcTransaction = MyCon.BeginTransaction()
        Try
            Dim quoteId As Integer
            Dim creationUserId As Integer
            Dim MethodsUnitId As Integer
            Dim VatId As Integer
            Dim customerId As Integer
            Dim customerAddressId As Integer
            Dim customerContactId As Integer
            Dim PaymentConditionsId As Integer
            Dim PaymentMethodsId As Integer
            Dim DeliveriesId As Integer

            '---------------------------------------------
            ' READ REFERENCE DATA (GUARDED)
            '---------------------------------------------
            MethodsUnitId = GetIdByCode(MyCon, tx, MethodsUnitsTable, MethodsUnitCode)
            If MethodsUnitId <= 0 Then Throw New Exception("WEM: Unité méthodes introuvable (code='" & MethodsUnitCode & "').")

            VatId = GetIdByCode(MyCon, tx, AccountingVatsTable, VATCode)
            If VatId <= 0 Then Throw New Exception("WEM: TVA introuvable (code='" & VATCode & "').")

            PaymentConditionsId = GetIdByCode(MyCon, tx, PaymentConditionsTable, PaymentConditionsCode)
            If PaymentConditionsId <= 0 Then Throw New Exception("WEM: Conditions de paiement introuvables (code='" & PaymentConditionsCode & "').")

            PaymentMethodsId = GetIdByCode(MyCon, tx, PaymentMethodsTable, PaymentMethodsCode)
            If PaymentMethodsId <= 0 Then Throw New Exception("WEM: Mode de paiement introuvable (code='" & PaymentMethodsCode & "').")

            DeliveriesId = GetIdByCode(MyCon, tx, DeliveriesTable, DeliveriesCode)
            If DeliveriesId <= 0 Then Throw New Exception("WEM: Mode de livraison introuvable (code='" & DeliveriesCode & "').")

            '---------------------------------------------
            ' READ CUSTOMER (company) INFORMATION
            '---------------------------------------------
            Using cmd As New OdbcCommand("SELECT id FROM " & CompaniesTable & " WHERE code = ? LIMIT 1", MyCon, tx)
                cmd.Parameters.Add(New OdbcParameter("@p1", OdbcType.VarChar) With {.Value = Convert.ToString(QUOTE.Site.ExternalId)})
                Dim obj As Object = cmd.ExecuteScalar()
                If obj IsNot Nothing AndAlso obj IsNot DBNull.Value Then customerId = Convert.ToInt32(obj)
            End Using
            If customerId <= 0 Then Throw New Exception("WEM: Société introuvable (companies.code='" & Convert.ToString(QUOTE.Site.ExternalId) & "').")

            '---------------------------------------------
            ' READ ADDRESS (best-effort)
            '---------------------------------------------
            customerAddressId = 0
            Using cmd As New OdbcCommand("SELECT id, adress, ZipCode, city FROM " & CompanieAddressesTable & " WHERE companies_id = ?", MyCon, tx)
                cmd.Parameters.Add(New OdbcParameter("@p1", OdbcType.Int) With {.Value = customerId})
                Using r As OdbcDataReader = cmd.ExecuteReader()
                    While r.Read()
                        Dim a As String = Normalize(Convert.ToString(r("adress")))
                        Dim z As String = Normalize(Convert.ToString(r("ZipCode")))
                        Dim c As String = Normalize(Convert.ToString(r("city")))

                        If String.Equals(a, Normalize(QUOTE.Site.Address), StringComparison.OrdinalIgnoreCase) AndAlso
                           String.Equals(z, Normalize(QUOTE.Site.Postcode), StringComparison.OrdinalIgnoreCase) AndAlso
                           String.Equals(c, Normalize(QUOTE.Site.City), StringComparison.OrdinalIgnoreCase) Then
                            customerAddressId = Convert.ToInt32(r("id"))
                            Exit While
                        End If
                    End While
                End Using
            End Using

            '---------------------------------------------
            ' READ CONTACT (best-effort)
            '---------------------------------------------
            customerContactId = 0
            Using cmd As New OdbcCommand("SELECT id, name, first_name FROM " & CompaniesContactTable & " WHERE companies_id = ?", MyCon, tx)
                cmd.Parameters.Add(New OdbcParameter("@p1", OdbcType.Int) With {.Value = customerId})
                Using r As OdbcDataReader = cmd.ExecuteReader()
                    While r.Read()
                        Dim n As String = Normalize(Convert.ToString(r("name")))
                        Dim f As String = Normalize(Convert.ToString(r("first_name")))

                        If String.Equals(n, Normalize(QUOTE.Contact.Surname), StringComparison.OrdinalIgnoreCase) AndAlso
                           String.Equals(f, Normalize(QUOTE.Contact.Forename), StringComparison.OrdinalIgnoreCase) Then
                            customerContactId = Convert.ToInt32(r("id"))
                            Exit While
                        End If
                    End While
                End Using
            End Using

            '---------------------------------------------
            ' READ USER INFORMATION (required)
            '---------------------------------------------
            creationUserId = 0
            Using cmd As New OdbcCommand("SELECT id FROM " & UserTable & " WHERE name = ? LIMIT 1", MyCon, tx)
                cmd.Parameters.Add(New OdbcParameter("@p1", OdbcType.VarChar) With {.Value = Normalize(Convert.ToString(Quote.Creator.OtherInformation))})
                Dim obj As Object = cmd.ExecuteScalar()
                If obj IsNot Nothing AndAlso obj IsNot DBNull.Value Then creationUserId = Convert.ToInt32(obj)
            End Using
            If creationUserId <= 0 Then Throw New Exception("WEM: Utilisateur créateur introuvable (users.name='" & Normalize(Convert.ToString(Quote.Creator.OtherInformation)) & "').")

            '---------------------------------------------
            ' CACHE METHODS SERVICES (code -> (id,type))
            '---------------------------------------------
            Dim serviceMap As New Dictionary(Of String, Tuple(Of Integer, Integer))(StringComparer.OrdinalIgnoreCase)
            Using cmd As New OdbcCommand("SELECT id, code, type FROM " & MethodsServicesTable, MyCon, tx)
                Using r As OdbcDataReader = cmd.ExecuteReader()
                    While r.Read()
                        Dim code As String = Convert.ToString(r("code")).Trim()
                        If Not String.IsNullOrWhiteSpace(code) Then
                            Dim id As Integer = Convert.ToInt32(r("id"))
                            Dim t As Integer = Convert.ToInt32(r("type"))
                            If Not serviceMap.ContainsKey(code) Then
                                serviceMap.Add(code, Tuple.Create(id, t))
                            End If
                        End If
                    End While
                End Using
            End Using

            '===========================================================
            ' FUNCTION FOR ADD OPERATION TO QUOTE LINE (parameterized)
            '===========================================================
            Dim CreatePartOperation As Action(Of RadQuote.Business.Operations.Overview.OperationResultsOnPart, Integer, Integer) =
                Sub(op As RadQuote.Business.Operations.Overview.OperationResultsOnPart, QuoteLineId As Integer, MethodsUnitIdLocal As Integer)

                    If op Is Nothing OrElse op.OperationDefinition Is Nothing Then Exit Sub

                    Dim svcCode As String = Convert.ToString(op.OperationDefinition.ExternalId).Trim()
                    If String.IsNullOrWhiteSpace(svcCode) Then Exit Sub
                    If Not serviceMap.ContainsKey(svcCode) Then Exit Sub

                    Dim svc As Tuple(Of Integer, Integer) = serviceMap(svcCode)
                    Dim operationId As Integer = svc.Item1
                    Dim operationType As Integer = svc.Item2

                    'Only productive services
                    If operationId <= 0 OrElse operationType <> 1 Then Exit Sub

                    Dim settingTime As Decimal = Math.Round(CDec(op.FullOtherTotalTime) / 100D, 2)
                    Dim unitTime As Decimal = Math.Round(CDec(op.FullUnitProductTime) / 100D, 2)
                    Dim unitCost As Decimal = Math.Round(CDec(op.UnitCost), 2)
                    Dim unitPrice As Decimal = Math.Round(CDec(op.UnitPrice), 2)

                    Dim sql As String =
                        "INSERT INTO " & TasksTable & " (" &
                            TaskLabel & "," &
                            TaskOrdre & "," &
                            TaskQuoteLineId & "," &
                            TaskServiceId & "," &
                            TaskStatuId & "," &
                            TaskType & "," &
                            TaskQty & "," &
                            TaskSettingTime & "," &
                            TaskUnitTime & "," &
                            TaskUnitCost & "," &
                            TaskUnitPrice & "," &
                            TaskMethodsUnitsId & "," &
                            TaskOrigin &
                        ") VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"

                    Using cmd As New OdbcCommand(sql, MyCon, tx)
                        cmd.Parameters.Add(New OdbcParameter("@p1", OdbcType.VarChar) With {.Value = Convert.ToString(op.OperationDefinition.Name)})
                        cmd.Parameters.Add(New OdbcParameter("@p2", OdbcType.Int) With {.Value = Convert.ToInt32(op.OperationDefinition.Index)})
                        cmd.Parameters.Add(New OdbcParameter("@p3", OdbcType.Int) With {.Value = QuoteLineId})
                        cmd.Parameters.Add(New OdbcParameter("@p4", OdbcType.Int) With {.Value = operationId})
                        cmd.Parameters.Add(New OdbcParameter("@p5", OdbcType.Int) With {.Value = 1}) 'status_id
                        cmd.Parameters.Add(New OdbcParameter("@p6", OdbcType.Int) With {.Value = operationType})
                        cmd.Parameters.Add(New OdbcParameter("@p7", OdbcType.Decimal) With {.Value = 1D}) 'qty
                        cmd.Parameters.Add(New OdbcParameter("@p8", OdbcType.Decimal) With {.Value = settingTime})
                        cmd.Parameters.Add(New OdbcParameter("@p9", OdbcType.Decimal) With {.Value = unitTime})
                        cmd.Parameters.Add(New OdbcParameter("@p10", OdbcType.Decimal) With {.Value = unitCost})
                        cmd.Parameters.Add(New OdbcParameter("@p11", OdbcType.Decimal) With {.Value = unitPrice})
                        cmd.Parameters.Add(New OdbcParameter("@p12", OdbcType.Int) With {.Value = MethodsUnitIdLocal})
                        cmd.Parameters.Add(New OdbcParameter("@p13", OdbcType.Int) With {.Value = 7}) 'origin
                        cmd.ExecuteNonQuery()
                    End Using
                End Sub

            '===========================================================
            ' FUNCTION FOR CREATE QUOTE LINE (parameterized + recursion)
            '===========================================================
            Dim CreateQuotePart As Action(Of RadQuote.Business.Parts.PartLine, Integer, Integer, Integer, Integer) = Nothing

            CreateQuotePart =
                Sub(p As RadQuote.Business.Parts.PartLine, quoteIdLocal As Integer, partIdLocal As Integer, MethodsUnitIdLocal As Integer, VatIdLocal As Integer)

                    If p Is Nothing OrElse p.Part Is Nothing Then Exit Sub

                    Dim QuoteLineId As Integer
                    Dim thickness As Decimal = 0D
                    Dim Material As String = ""
					Dim bendCount As Integer = 0
					Dim symPath As String = ""

                    'Best-effort material/thickness
                    If TypeOf (p.Part) Is RadQuote.Business.Parts.SymbolPart Then
						Dim sp As RadQuote.Business.Parts.SymbolPart = CType(p.Part, RadQuote.Business.Parts.SymbolPart)

						'--- EXISTANT : material / thickness ---
						Dim mat = RadQuote.Business.Materials.Materials.Current.GetMaterial(sp.MaterialId)
						If mat IsNot Nothing Then Material = Convert.ToString(mat.Name)

						Dim tech = mat.Thicknesses.GetTechnology(sp.ThicknessId)
						If tech IsNot Nothing Then
							Dim raw As String = Convert.ToString(tech.Description)
							Dim num As Decimal
							If Decimal.TryParse(raw, NumberStyles.Any, CultureInfo.InvariantCulture, num) Then
								thickness = num
							ElseIf Decimal.TryParse(raw, NumberStyles.Any, CultureInfo.CurrentCulture, num) Then
								thickness = num
							Else
								thickness = 0D
							End If
						End If

						'--- AJOUT : chemin SYM + nombre de plis ---
						Try
							If sp.Sym IsNot Nothing Then
								symPath = Convert.ToString(sp.Sym.FilePath)
							End If
						Catch
							symPath = ""
						End Try

						bendCount = GetNumberOfBends(sp)
					End If

					

                    Dim nowTs As DateTime = DateTime.Now

                    'Insert quote line
                    Dim sqlLine As String =
                        "INSERT INTO " & QuoteLinesTable & " (" &
                            QuoteLineQuoteId & "," &
                            QuoteLineOrdre & "," &
                            QuoteLineCode & "," &
                            QuoteLineLabel & "," &
                            QuoteLineQty & "," &
                            QuoteLineMethodsUnitsId & "," &
                            QuoteLineSellingPrice & "," &
                            QuoteLineAccountingVatsId & "," &
                            CreatedAt & "," &
                            UpdatedAt &
                        ") VALUES (?,?,?,?,?,?,?,?,?,?)"

                    Using cmd As New OdbcCommand(sqlLine, MyCon, tx)
                        cmd.Parameters.Add(New OdbcParameter("@p1", OdbcType.Int) With {.Value = quoteIdLocal})
                        cmd.Parameters.Add(New OdbcParameter("@p2", OdbcType.Int) With {.Value = partIdLocal}) 'keep your original behavior
                        cmd.Parameters.Add(New OdbcParameter("@p3", OdbcType.VarChar) With {.Value = Convert.ToString(p.Part.ID)})
                        cmd.Parameters.Add(New OdbcParameter("@p4", OdbcType.VarChar) With {.Value = Convert.ToString(p.Part.Names(0))})
                        cmd.Parameters.Add(New OdbcParameter("@p5", OdbcType.Decimal) With {.Value = CDec(p.Part.Quantity)})
                        cmd.Parameters.Add(New OdbcParameter("@p6", OdbcType.Int) With {.Value = MethodsUnitIdLocal})
                        cmd.Parameters.Add(New OdbcParameter("@p7", OdbcType.Decimal) With {.Value = CDec(p.Part.SoldUnitPrice)})
                        cmd.Parameters.Add(New OdbcParameter("@p8", OdbcType.Int) With {.Value = VatIdLocal})
                        cmd.Parameters.Add(New OdbcParameter("@p9", OdbcType.DateTime) With {.Value = nowTs})
                        cmd.Parameters.Add(New OdbcParameter("@p10", OdbcType.DateTime) With {.Value = nowTs})
                        cmd.ExecuteNonQuery()
                    End Using

                    QuoteLineId = GetLastInsertId(MyCon, tx)

                    messages.Add("Traitement de l'élément " & Convert.ToString(p.Part.Names(0)) & " réussi. (quote_line_id=" & QuoteLineId & ")")

                    'Insert details
                    Dim sqlDetails As String =
						"INSERT INTO " & QuoteLinesDetailsTable & " (" &
							QuoteLineDetailsQuoteId & "," &
							QuoteLineDetailsXsize & "," &
							QuoteLineDetailsYsize & "," &
							QuoteLineDetailsZsize & "," &
							QuoteLineDetailsMaterial & "," &
							QuoteLineDetailsThickness & "," &
							QuoteLineDetailsWeight & "," &
							QuoteLineDetailsBendCount & "," &
							QuoteLineDetailsCadFilePath & "," &
							QuoteLineDetailsComment & "," &
							CreatedAt & "," &
							UpdatedAt &
						") VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"

                    Using cmd As New OdbcCommand(sqlDetails, MyCon, tx)
						cmd.Parameters.Add(New OdbcParameter("@p1", OdbcType.Int) With {.Value = QuoteLineId})
						cmd.Parameters.Add(New OdbcParameter("@p2", OdbcType.Decimal) With {.Value = CDec(p.Part.X)})
						cmd.Parameters.Add(New OdbcParameter("@p3", OdbcType.Decimal) With {.Value = CDec(p.Part.Y)})
						cmd.Parameters.Add(New OdbcParameter("@p4", OdbcType.Decimal) With {.Value = CDec(p.Part.Z)})
						cmd.Parameters.Add(New OdbcParameter("@p5", OdbcType.VarChar) With {.Value = Material})
						cmd.Parameters.Add(New OdbcParameter("@p6", OdbcType.Decimal) With {.Value = thickness})
						cmd.Parameters.Add(New OdbcParameter("@p7", OdbcType.Decimal) With {.Value = CDec(p.Part.Weight)})

						'AJOUTS
						cmd.Parameters.Add(New OdbcParameter("@p8", OdbcType.Int) With {.Value = bendCount})
						cmd.Parameters.Add(New OdbcParameter("@p9", OdbcType.VarChar) With {.Value = symPath})

						cmd.Parameters.Add(New OdbcParameter("@p10", OdbcType.VarChar) With {.Value = SafeText(Convert.ToString(p.Part.Comment))})
						cmd.Parameters.Add(New OdbcParameter("@p11", OdbcType.DateTime) With {.Value = nowTs})
						cmd.Parameters.Add(New OdbcParameter("@p12", OdbcType.DateTime) With {.Value = nowTs})
						cmd.ExecuteNonQuery()
					End Using

                    'Tasks from operations
                    For Each op As RadQuote.Business.Operations.Result.OperationResult In p.Part.OperationCalculations
                        If op IsNot Nothing AndAlso op.IsUsedInCalculations AndAlso
                           TypeOf (op.OperationDefinition) Is RadQuote.Business.Operations.PartOperation Then

                            Dim OperationResults As RadQuote.Business.Operations.Overview.OperationResultsOnPart =
                                p.OperationsResults.FirstOrDefault(Function(op2) op2.OperationDefinition.Name = op.OperationDefinition.Name)

                            CreatePartOperation(OperationResults, QuoteLineId, MethodsUnitIdLocal)
                        End If
                    Next

                    'Recurse
                    For Each sp As RadQuote.Business.Parts.PartLine In p.SubParts
                        CreateQuotePart(sp, quoteIdLocal, partIdLocal, MethodsUnitIdLocal, VatIdLocal)
                    Next
                End Sub

            '---------------------------------------------
            ' CREATE QUOTE
            '---------------------------------------------
            Dim QuoteGuid As Guid = Guid.NewGuid()
            Dim QuoteGuidAsString As String = QuoteGuid.ToString()
            Dim now As DateTime = DateTime.Now

            Dim sqlQuote As String =
                "INSERT INTO " & QuoteTable & " (" &
                    QuoteUUID & "," &
                    QuoteCode & "," &
                    QuoteLabel & "," &
                    QuoteReference & "," &
                    QuoteCustomerId & "," &
                    QuoteCustomerContactId & "," &
                    QuoteCustomerAddressId & "," &
                    QuoteValidityDate & "," &
                    QuoteUserId & "," &
                    QuotePaymentConditionsId & "," &
                    QuotePaymentMethodsId & "," &
                    QuoteDeliveriesId & "," &
                    QuoteComment & "," &
                    CreatedAt & "," &
                    UpdatedAt &
                ") VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"

            Using cmd As New OdbcCommand(sqlQuote, MyCon, tx)
                cmd.Parameters.Add(New OdbcParameter("@p1", OdbcType.VarChar) With {.Value = QuoteGuidAsString})
                cmd.Parameters.Add(New OdbcParameter("@p2", OdbcType.VarChar) With {.Value = Convert.ToString(Quote.Name)})
                cmd.Parameters.Add(New OdbcParameter("@p3", OdbcType.VarChar) With {.Value = Convert.ToString(Quote.Name)})
                cmd.Parameters.Add(New OdbcParameter("@p4", OdbcType.VarChar) With {.Value = SafeText(GetQuoteReference())})
                cmd.Parameters.Add(New OdbcParameter("@p5", OdbcType.Int) With {.Value = customerId})

                'Best-effort: store NULL if not found (avoid inserting 0 when FK expects NULL)
                cmd.Parameters.Add(New OdbcParameter("@p6", OdbcType.Int) With {.Value = If(customerContactId > 0, CType(customerContactId, Object), DBNull.Value)})
                cmd.Parameters.Add(New OdbcParameter("@p7", OdbcType.Int) With {.Value = If(customerAddressId > 0, CType(customerAddressId, Object), DBNull.Value)})

                cmd.Parameters.Add(New OdbcParameter("@p8", OdbcType.Date) With {.Value = now.Date})
                cmd.Parameters.Add(New OdbcParameter("@p9", OdbcType.Int) With {.Value = creationUserId})
                cmd.Parameters.Add(New OdbcParameter("@p10", OdbcType.Int) With {.Value = PaymentConditionsId})
                cmd.Parameters.Add(New OdbcParameter("@p11", OdbcType.Int) With {.Value = PaymentMethodsId})
                cmd.Parameters.Add(New OdbcParameter("@p12", OdbcType.Int) With {.Value = DeliveriesId})
                cmd.Parameters.Add(New OdbcParameter("@p13", OdbcType.VarChar) With {.Value = SafeText(GetQuoteComment())})
                cmd.Parameters.Add(New OdbcParameter("@p14", OdbcType.DateTime) With {.Value = now})
                cmd.Parameters.Add(New OdbcParameter("@p15", OdbcType.DateTime) With {.Value = now})
                cmd.ExecuteNonQuery()
            End Using

            messages.Add("Traitement du devis " & Convert.ToString(Quote.Name) & " réussi.")

            quoteId = GetLastInsertId(MyCon, tx)

            '---------------------------------------------
            ' EXPORT PARTS
            '---------------------------------------------
            For Each p As RadQuote.Business.Parts.PartLine In QUOTE.Parts.SubParts
                CreateQuotePart(p, quoteId, 0, MethodsUnitId, VatId)
            Next

            '---------------------------------------------
            ' COMMIT
            '---------------------------------------------
            tx.Commit()

        Catch ex As Exception
            Try
                tx.Rollback()
            Catch
            End Try
            messages.Add("ERREUR export : " & ex.Message)
        End Try
    End Using
End If

MyCon.Close()

'---------------------------------------------
' Result message
'---------------------------------------------
Dim resultMessage As String = String.Join(vbCrLf, messages)
RadWin.ShowMsgBox("Export", "Résultats des traitements :" & vbCrLf & resultMessage)

