'===========================================================
' RADQuote -> WEM export script (MySQL via ODBC)
' Version compatible moteur script RadQuote:
' - PAS de Option/Imports
' - Types fully-qualified (System.Data.Odbc...)
' - PAS de paramètres ODBC (ODBC 8.4 : bugs Bind fréquents)
' - Escape des backslashes (\) pour MySQL (chemins Windows)
' - Champs companies_addresses corrigés: adress / zipcode
'===========================================================

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
Dim BaseUrl  As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_BaseUrl", "").Value.StringValue

'-----------------------------------------------------------
' Database table names
'-----------------------------------------------------------
Const QuoteTable As String = "quotes"
Const QuoteLinesTable As String = "quote_lines"
Const QuoteLinesDetailsTable As String = "quote_line_details"
Const TasksTable As String = "tasks"
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
Const QuoteLineLabel As String = "label"
Const QuoteLineQty As String = "qty"
Const QuoteLineMethodsUnitsId As String = "methods_units_id"
Const QuoteLineSellingPrice As String = "selling_price"
Const QuoteLineAccountingVatsId As String = "accounting_vats_id"

Const QuoteLineDetailsQuoteId As String = "quote_lines_id"
Const QuoteLineDetailsXsize As String = "x_size"
Const QuoteLineDetailsYsize As String = "y_size"
Const QuoteLineDetailsZsize As String = "z_size"
Const QuoteLineDetailsMaterial As String = "material"
Const QuoteLineDetailsThickness As String = "thickness"
Const QuoteLineDetailsWeight As String = "weight"
Const QuoteLineDetailsBendCount As String = "bend_count"
Const QuoteLineDetailsCamFilePath As String = "cam_file_path"
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
' Helpers
'-----------------------------------------------------------
Dim messages As New System.Collections.Generic.List(Of String)()
Dim nowTs As DateTime = DateTime.Now
Dim now As DateTime = DateTime.Now
		
' Escape simple quotes
Dim SqlQ As Func(Of String, String) =
    Function(s As String) As String
        If s Is Nothing Then Return ""
        Return s.Replace("'", "''")
    End Function

' Escape Windows paths for MySQL strings:
' - double backslashes \ -> \\ so MySQL doesn't eat them
' - escape quotes too
Dim SqlPath As Func(Of String, String) =
    Function(s As String) As String
        If s Is Nothing Then Return ""
        Dim t As String = s
        t = t.Replace("\", "\\")
        t = t.Replace("'", "''")
        Return t
    End Function

Dim Trunc As Func(Of String, Integer, String) =
    Function(s As String, maxLen As Integer) As String
        If s Is Nothing Then Return ""
        Dim t As String = s
        If t.Length <= maxLen Then Return t
        Return t.Substring(0, maxLen)
    End Function


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
        Return s.Replace(vbCrLf, "|").Replace(vbLf, "|").Replace(vbCr, "|")
    End Function

Dim GetQuoteReference As Func(Of String) =
    Function() As String
        Dim detail = QUOTE.Details.FirstOrDefault(Function(d) String.Equals(d.ExternalId, "REFERENCE", StringComparison.OrdinalIgnoreCase))
        If detail Is Nothing OrElse detail.Value Is Nothing Then Return ""
        Dim reference As String = detail.Value.Value
        If detail.IsRichComment Then reference = SafeText(reference)
        Return reference
    End Function

Dim GetQuoteComment As Func(Of String) =
    Function() As String
        Dim detail = QUOTE.Details.FirstOrDefault(Function(d) String.Equals(d.ExternalId, "COMMENT", StringComparison.OrdinalIgnoreCase))
        If detail Is Nothing OrElse detail.Value Is Nothing Then Return ""
        Dim c As String = detail.Value.Value
        If detail.IsRichComment Then c = SafeText(c)
        Return c
    End Function

Dim GetNumberOfBends As Func(Of RadQuote.Business.Parts.SymbolPart, Integer) =
    Function(sym As RadQuote.Business.Parts.SymbolPart) As Integer
        If sym Is Nothing OrElse sym.Sym Is Nothing Then Return 0
        Try
            If sym.Sym.HasQuotationInfo(RADev.Radan.QuotationInfoNum.NumberOfBends) Then
                Dim q As Integer = sym.Sym.IntQuotationSingleValue(RADev.Radan.QuotationInfoNum.NumberOfBends)
                If q > 0 Then Return q
            End If
        Catch
        End Try
        Try
            If sym.Sym.HasAttribute(500) Then
                Dim v As String = Convert.ToString(sym.Sym.GetAttribute(500).Value)
                Dim n As Integer
                If Integer.TryParse(v, n) AndAlso n > 0 Then Return n
            End If
        Catch
        End Try
        Return 0
    End Function

Dim ExecuteScalarInt As Func(Of System.Data.Odbc.OdbcConnection, System.Data.Odbc.OdbcTransaction, String, Integer) =
    Function(cn As System.Data.Odbc.OdbcConnection, tx As System.Data.Odbc.OdbcTransaction, sql As String) As Integer
        Dim cmd As New System.Data.Odbc.OdbcCommand(sql, cn, tx)
        Dim obj As Object = cmd.ExecuteScalar()
        If obj Is Nothing OrElse obj Is DBNull.Value Then Return 0
        Return Convert.ToInt32(obj)
    End Function

Dim ExecuteNonQuery As Action(Of System.Data.Odbc.OdbcConnection, System.Data.Odbc.OdbcTransaction, String) =
    Sub(cn As System.Data.Odbc.OdbcConnection, tx As System.Data.Odbc.OdbcTransaction, sql As String)
        Dim cmd As New System.Data.Odbc.OdbcCommand(sql, cn, tx)
        cmd.ExecuteNonQuery()
    End Sub

Dim GetLastInsertId As Func(Of System.Data.Odbc.OdbcConnection, System.Data.Odbc.OdbcTransaction, Integer) =
    Function(cn As System.Data.Odbc.OdbcConnection, tx As System.Data.Odbc.OdbcTransaction) As Integer
        Return ExecuteScalarInt(cn, tx, "SELECT LAST_INSERT_ID()")
    End Function

Dim GetIdByCode As Func(Of System.Data.Odbc.OdbcConnection, System.Data.Odbc.OdbcTransaction, String, String, Integer) =
    Function(cn As System.Data.Odbc.OdbcConnection, tx As System.Data.Odbc.OdbcTransaction, tableName As String, code As String) As Integer
        If String.IsNullOrWhiteSpace(code) Then Return 0
        Dim sql As String = "SELECT id FROM " & tableName & " WHERE code = '" & SqlQ(code.Trim()) & "' LIMIT 1"
        Return ExecuteScalarInt(cn, tx, sql)
    End Function

'-----------------------------------------------------------
' Open connection
'-----------------------------------------------------------
Dim MyCon As New System.Data.Odbc.OdbcConnection()
MyCon.ConnectionString = "Driver={MySQL ODBC 8.4 ANSI Driver};DATABASE=" & DatabaseName & ";SERVER=" & ServerIp & ";PORT=" & ServerPort & ";UID=" & DatabaseUser & ";PASSWORD=" & DatabasePassword & ";"
MyCon.Open()

If MyCon.State = System.Data.ConnectionState.Open Then

    Dim tx As System.Data.Odbc.OdbcTransaction = MyCon.BeginTransaction()

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
        Dim customerCode As String = Convert.ToString(QUOTE.Site.ExternalId)
        Dim sqlCustomer As String = "SELECT id FROM " & CompaniesTable & " WHERE code = '" & SqlQ(customerCode) & "' LIMIT 1"
        customerId = ExecuteScalarInt(MyCon, tx, sqlCustomer)
        If customerId <= 0 Then Throw New Exception("WEM: Société introuvable (companies.code='" & customerCode & "').")

        '---------------------------------------------
        ' READ ADDRESS (best-effort)  <-- champs corrigés
        ' companies_addresses: adress / zipcode / city
        '---------------------------------------------
        customerAddressId = 0
        Dim sqlAddr As String =
            "SELECT id, adress, zipcode, city " &
            "FROM " & CompanieAddressesTable & " " &
            "WHERE companies_id = " & customerId & " " &
            "ORDER BY `default` DESC, ordre ASC, id ASC"

        Dim cmdAddr As New System.Data.Odbc.OdbcCommand(sqlAddr, MyCon, tx)
        Dim rAddr As System.Data.Odbc.OdbcDataReader = cmdAddr.ExecuteReader()
        While rAddr.Read()
            Dim a As String = Normalize(Convert.ToString(rAddr("adress")))
            Dim z As String = Normalize(Convert.ToString(rAddr("zipcode")))
            Dim c As String = Normalize(Convert.ToString(rAddr("city")))

            If String.Equals(a, Normalize(QUOTE.Site.Address), StringComparison.OrdinalIgnoreCase) AndAlso
               String.Equals(z, Normalize(QUOTE.Site.Postcode), StringComparison.OrdinalIgnoreCase) AndAlso
               String.Equals(c, Normalize(QUOTE.Site.City), StringComparison.OrdinalIgnoreCase) Then
                customerAddressId = Convert.ToInt32(rAddr("id"))
                Exit While
            End If
        End While
        rAddr.Close()

        '---------------------------------------------
        ' READ CONTACT (best-effort)
        ' companies_contacts: name / first_name
        '---------------------------------------------
        customerContactId = 0
        Dim sqlContact As String =
            "SELECT id, name, first_name " &
            "FROM " & CompaniesContactTable & " " &
            "WHERE companies_id = " & customerId & " " &
            "ORDER BY `default` DESC, ordre ASC, id ASC"

        Dim cmdContact As New System.Data.Odbc.OdbcCommand(sqlContact, MyCon, tx)
        Dim rContact As System.Data.Odbc.OdbcDataReader = cmdContact.ExecuteReader()
        While rContact.Read()
            Dim n As String = Normalize(Convert.ToString(rContact("name")))
            Dim f As String = Normalize(Convert.ToString(rContact("first_name")))

            If QUOTE.Contact IsNot Nothing Then
                If String.Equals(n, Normalize(QUOTE.Contact.Surname), StringComparison.OrdinalIgnoreCase) AndAlso
                   String.Equals(f, Normalize(QUOTE.Contact.Forename), StringComparison.OrdinalIgnoreCase) Then
                    customerContactId = Convert.ToInt32(rContact("id"))
                    Exit While
                End If
            End If
        End While
        rContact.Close()

        '---------------------------------------------
        ' READ USER INFORMATION (required)
        '---------------------------------------------
        Dim creatorName As String = Normalize(Convert.ToString(Quote.LastEditor.Name))
        creationUserId = ExecuteScalarInt(MyCon, tx, "SELECT id FROM " & UserTable & " WHERE name = '" & SqlQ(creatorName) & "' LIMIT 1")
        If creationUserId <= 0 Then Throw New Exception("WEM: Utilisateur créateur introuvable (users.name='" & creatorName & "').")

        '---------------------------------------------
        ' CACHE METHODS SERVICES (code -> (id,type))
        '---------------------------------------------
        Dim serviceMap As New System.Collections.Generic.Dictionary(Of String, System.Tuple(Of Integer, Integer))(StringComparer.OrdinalIgnoreCase)
        Dim cmdSvc As New System.Data.Odbc.OdbcCommand("SELECT id, code, type FROM " & MethodsServicesTable, MyCon, tx)
        Dim rSvc As System.Data.Odbc.OdbcDataReader = cmdSvc.ExecuteReader()
        While rSvc.Read()
            Dim code As String = Convert.ToString(rSvc("code")).Trim()
            If Not String.IsNullOrWhiteSpace(code) Then
                Dim id As Integer = Convert.ToInt32(rSvc("id"))
                Dim t As Integer = Convert.ToInt32(rSvc("type"))
                If Not serviceMap.ContainsKey(code) Then
                    serviceMap.Add(code, System.Tuple.Create(id, t))
                End If
            End If
        End While
        rSvc.Close()

        '===========================================================
        ' FUNCTION FOR ADD OPERATION TO QUOTE LINE
        '===========================================================
        Dim CreatePartOperation As Action(Of RadQuote.Business.Operations.Overview.OperationResultsOnPart, Integer, Integer) =
            Sub(op As RadQuote.Business.Operations.Overview.OperationResultsOnPart, QuoteLineId As Integer, MethodsUnitIdLocal As Integer)

                If op Is Nothing OrElse op.OperationDefinition Is Nothing Then Exit Sub

                Dim svcCode As String = Convert.ToString(op.OperationDefinition.ExternalId).Trim()
                If String.IsNullOrWhiteSpace(svcCode) Then Exit Sub
                If Not serviceMap.ContainsKey(svcCode) Then Exit Sub

                Dim svc As System.Tuple(Of Integer, Integer) = serviceMap(svcCode)
                Dim operationId As Integer = svc.Item1
                Dim operationType As Integer = svc.Item2

                If operationId <= 0 OrElse operationType <> 1 Then Exit Sub 'productive only

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
                        TaskOrigin & "," &
                        CreatedAt & "," &
                        UpdatedAt &
                    ") VALUES (" &
                        "'" & SqlQ(Convert.ToString(op.OperationDefinition.Name)) & "'," &
                        Convert.ToInt32(op.OperationDefinition.Index) & "," &
                        QuoteLineId & "," &
                        operationId & "," &
                        "1," &
                        operationType & "," &
                        "1," &
                        Replace(Convert.ToString(settingTime), ",", ".") & "," &
                        Replace(Convert.ToString(unitTime), ",", ".") & "," &
                        Replace(Convert.ToString(unitCost), ",", ".") & "," &
                        Replace(Convert.ToString(unitPrice), ",", ".") & "," &
                        MethodsUnitIdLocal & "," &
                        "7," &
                        "'" & nowTs.ToString("yyyy-MM-dd HH:mm:ss") & "'," &
                        "'" & nowTs.ToString("yyyy-MM-dd HH:mm:ss") & "'" &
                    ")"

                ExecuteNonQuery(MyCon, tx, sql)
            End Sub

        '===========================================================
        ' FUNCTION FOR CREATE QUOTE LINE (recursion)
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

                If TypeOf (p.Part) Is RadQuote.Business.Parts.SymbolPart Then
                    Dim sp As RadQuote.Business.Parts.SymbolPart = CType(p.Part, RadQuote.Business.Parts.SymbolPart)

                    Material = sp.GetThickness().ExternalId
                    If String.IsNullOrWhiteSpace(Material) Then
                        Material = sp.GetMaterial().ExternalId
                    End If

                    thickness = sp.ThicknessValue

                    Try
                        If sp.Sym IsNot Nothing Then symPath = Convert.ToString(sp.Sym.FilePath)
                    Catch
                        symPath = ""
                    End Try

                    bendCount = GetNumberOfBends(sp)
                End If


                '---------------------------------------
                ' 1) INSERT quote_lines
                '---------------------------------------
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
                    ") VALUES (" &
                        quoteIdLocal & "," &
                        partIdLocal & "," &
                        "'" & SqlQ(Convert.ToString(p.Part.ID)) & "'," &
                        "'" & SqlQ(Convert.ToString(p.Part.Names(0))) & "'," &
                        Replace(Convert.ToString(CDec(p.Part.Quantity)), ",", ".") & "," &
                        MethodsUnitIdLocal & "," &
                        Replace(Convert.ToString(CDec(p.Part.SoldUnitPrice)), ",", ".") & "," &
                        VatIdLocal & "," &
                        "'" & nowTs.ToString("yyyy-MM-dd HH:mm:ss") & "'," &
                        "'" & nowTs.ToString("yyyy-MM-dd HH:mm:ss") & "'" &
                    ")"

                ExecuteNonQuery(MyCon, tx, sqlLine)
                QuoteLineId = GetLastInsertId(MyCon, tx)

                messages.Add("Traitement de l'élément " & Convert.ToString(p.Part.Names(0)) & " réussi. (quote_line_id=" & QuoteLineId & ")")

                '---------------------------------------
				' 2) INSERT quote_line_details (SANS paramètres, avec escape du chemin)
				'---------------------------------------
				Dim safePath As String = If(symPath, "")
				Dim safeComment As String = SqlQ(SafeText(Convert.ToString(p.Part.Comment)))
				Dim safeMaterial As String = SqlQ(If(Material, ""))

				Dim sqlDetails As String = _
					"INSERT INTO " & QuoteLinesDetailsTable & " (" & _
						QuoteLineDetailsQuoteId & "," & _
						QuoteLineDetailsXsize & "," & _
						QuoteLineDetailsYsize & "," & _
						QuoteLineDetailsZsize & "," & _
						QuoteLineDetailsMaterial & "," & _
						QuoteLineDetailsThickness & "," & _
						QuoteLineDetailsWeight & "," & _
						QuoteLineDetailsBendCount & "," & _
						QuoteLineDetailsCamFilePath & "," & _
						QuoteLineDetailsComment & "," & _
						CreatedAt & "," & _
						UpdatedAt & _
					") VALUES (" & _
						QuoteLineId & "," & _
						Replace(Convert.ToString(CDec(p.Part.X)), ",", ".") & "," & _
						Replace(Convert.ToString(CDec(p.Part.Y)), ",", ".") & "," & _
						Replace(Convert.ToString(CDec(p.Part.Z)), ",", ".") & "," & _
						"'" & safeMaterial & "'," & _
						Replace(Convert.ToString(thickness), ",", ".") & "," & _
						Replace(Convert.ToString(CDec(p.Part.Weight)), ",", ".") & "," & _
						bendCount & "," & _
						"'" & SqlPath(safePath) & "'," & _
						"'" & safeComment & "'," & _
						"'" & nowTs.ToString("yyyy-MM-dd HH:mm:ss") & "'," & _
						"'" & nowTs.ToString("yyyy-MM-dd HH:mm:ss") & "'" & _
					")"

				ExecuteNonQuery(MyCon, tx, sqlDetails)

                'Tasks from operations
                For Each op As RadQuote.Business.Operations.Result.OperationResult In p.Part.OperationCalculations
                    If op IsNot Nothing AndAlso op.IsUsedInCalculations AndAlso TypeOf (op.OperationDefinition) Is RadQuote.Business.Operations.PartOperation Then
                        Dim OperationResults = p.OperationsResults.FirstOrDefault(Function(op2) op2.OperationDefinition.Name = op.OperationDefinition.Name)
                        CreatePartOperation(OperationResults, QuoteLineId, MethodsUnitIdLocal)
                    End If
                Next

                'Recurse sub parts
                For Each sp2 As RadQuote.Business.Parts.PartLine In p.SubParts
                    CreateQuotePart(sp2, quoteIdLocal, partIdLocal, MethodsUnitIdLocal, VatIdLocal)
                Next

            End Sub

		'---------------------------------------------
		' CHECK IF QUOTE ALREADY EXISTS (by code + customer)
		'---------------------------------------------
		Dim quoteBaseName As String = Convert.ToString(Quote.Name)
		Dim existingQuoteId As Integer = ExecuteScalarInt(
			MyCon, tx,
			"SELECT id FROM " & QuoteTable &
			" WHERE " & QuoteCode & " = '" & SqlQ(quoteBaseName) & "'" &
			" AND " & QuoteCustomerId & " = " & customerId &
			" LIMIT 1"
		)

		Dim quoteNameUsed As String = quoteBaseName

		If existingQuoteId > 0 Then
			Dim prompt As String =
				"Un devis existe déjà en base avec le même code." & vbCrLf &
				"Code : " & quoteBaseName & vbCrLf &
				"ID : " & existingQuoteId & vbCrLf & vbCrLf &
				"Oui  = Créer un NOUVEAU devis" & vbCrLf &
				"Non  = Créer une NOUVELLE VERSION (MAJ date/heure)" & vbCrLf &
				"Annuler = Stopper l'export"

			Dim res As Microsoft.VisualBasic.MsgBoxResult =
				MsgBox(prompt, Microsoft.VisualBasic.MsgBoxStyle.YesNoCancel Or Microsoft.VisualBasic.MsgBoxStyle.Question, "WEM Export")

			If res = Microsoft.VisualBasic.MsgBoxResult.Cancel Then
				Throw New Exception("Export annulé : devis déjà existant (id=" & existingQuoteId & ").")
			ElseIf res = Microsoft.VisualBasic.MsgBoxResult.No Then
				' new version name (short)
				Dim suffix As String = " MAJ " & now.ToString("yyyyMMdd-HHmm")
				quoteNameUsed = Trunc(quoteBaseName & suffix, 255)
			Else
				' Yes -> keep same name (new quote)
				quoteNameUsed = quoteBaseName
			End If
		End If

        '---------------------------------------------
        ' CREATE QUOTE
        '---------------------------------------------
        Dim QuoteGuid As Guid = Guid.NewGuid()
        Dim QuoteGuidAsString As String = QuoteGuid.ToString()

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
            ") VALUES (" &
                "'" & SqlQ(QuoteGuidAsString) & "'," &
                "'" & SqlQ(Convert.ToString(quoteNameUsed)) & "'," &
                "'" & SqlQ(Convert.ToString(quoteNameUsed)) & "'," &
                "'" & SqlQ(SafeText(GetQuoteReference())) & "'," &
                customerId & "," &
                If(customerContactId > 0, customerContactId.ToString(), "NULL") & "," &
                If(customerAddressId > 0, customerAddressId.ToString(), "NULL") & "," &
                "'" & now.ToString("yyyy-MM-dd") & "'," &
                creationUserId & "," &
                PaymentConditionsId & "," &
                PaymentMethodsId & "," &
                DeliveriesId & "," &
                "'" & SqlQ(SafeText(GetQuoteComment())) & "'," &
                "'" & now.ToString("yyyy-MM-dd HH:mm:ss") & "'," &
                "'" & now.ToString("yyyy-MM-dd HH:mm:ss") & "'" &
            ")"

        ExecuteNonQuery(MyCon, tx, sqlQuote)
        quoteId = GetLastInsertId(MyCon, tx)

		Dim quoteUrl As String = ""

		If Not String.IsNullOrWhiteSpace(BaseUrl) Then
			quoteUrl = BaseUrl.TrimEnd("/"c) & "/fr/quotes/" & quoteId.ToString()
		End If

        Dim msg As String =
			"Traitement du devis " & Convert.ToString(Quote.Name) & _
			" réussi." & vbCrLf & _
			"ID devis : " & quoteId

		If quoteUrl <> "" Then
			msg &= vbCrLf & "Lien devis : " & quoteUrl
			 Try
				System.Diagnostics.Process.Start("explorer.exe", quoteUrl)
			Catch ex As Exception
				' bloqué / non autorisé dans ce moteur -> on ignore
			End Try
		End If

		messages.Add(msg)

        '---------------------------------------------
        ' EXPORT PARTS
        '---------------------------------------------
        For Each p As RadQuote.Business.Parts.PartLine In QUOTE.Parts.SubParts
            CreateQuotePart(p, quoteId, 0, MethodsUnitId, VatId)
        Next

        tx.Commit()

    Catch ex As Exception
        Try
            tx.Rollback()
        Catch rollEx As Exception
            messages.Add("ERREUR ROLLBACK : " & rollEx.GetType().FullName & " | " & rollEx.Message)
        End Try

        messages.Add("ERREUR export : " & ex.GetType().FullName)
        messages.Add("Message : " & If(String.IsNullOrWhiteSpace(ex.Message), "(vide)", ex.Message))
        messages.Add("StackTrace : " & If(ex.StackTrace, "(null)"))
        messages.Add("ToString : " & ex.ToString())
    End Try
End If

Try : MyCon.Close() : Catch : End Try

Dim resultMessage As String = String.Join(vbCrLf, messages)
RadWin.ShowMsgBox("Export", "Résultats des traitements :" & vbCrLf & resultMessage)
