
'Variables from RADQuote Constants
Dim ServerIp As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Serveur_IP","localhost").Value.StringValue
Dim ServerPort As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Serveur_port","3306").Value.StringValue
Dim DatabaseName As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Nom_base","erp").Value.StringValue
Dim DatabaseUser As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Utilisateur","").Value.StringValue
Dim DatabasePassword As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Mot_de_passe","").Value.StringValue
Dim MethodsUnitCode As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Methods_Unit_Code","").Value.StringValue
Dim VATCode As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_VAT_Code","").Value.StringValue
Dim DeliveriesCode As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Deliveries_Code","").Value.StringValue
Dim PaymentConditionsCode As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Payment_Conditions_Code","").Value.StringValue
Dim PaymentMethodsCode As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Payment_Methods_Code","").Value.StringValue

'Database table names
Const QuoteTable As String = "quotes"
Const QuoteLinesTable As String = "quote_lines"
Const QuoteLinesDetailsTable As String = "quote_line_details"
Const TasksTable As String = "tasks"
Const SubAssembliesTable As String = "sub_assemblies"
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


'Database column names
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
Const QuoteLineProductId As String = "product_id"
Const QuoteLineLabel As String = "label"
Const QuoteLineQty As String = "qty"
Const QuoteLineMethodsUnitsId As String = "methods_units_id"
Const QuoteLineSellingPrice As String = "selling_price"
Const QuoteLineDiscount As String = "discount"
Const QuoteLineAccountingVatsId As String = "accounting_vats_id"
Const QuoteLineDeliveryDate As String = "delivery_date"

Const QuoteLineDetailsQuoteId As String = "quote_lines_id"
Const QuoteLineDetailsXsize As String = "x_size"
Const QuoteLineDetailsYsize As String = "y_size"
Const QuoteLineDetailsZsize As String = "z_size"
Const QuoteLineDetailsXoversizesize As String = "x_oversize"
Const QuoteLineDetailsYoversizesize As String = "y_oversize"
Const QuoteLineDetailsZoversizesize As String = "z_oversize"
Const QuoteLineDetailsDiameter As String = "diameter"
Const QuoteLineDetailsMaterial As String = "material"
Const QuoteLineDetailsThickness As String = "thickness"
Const QuoteLineDetailsWeight As String = "weight"
Const QuoteLineDetailsMaterialLossRate As String = "material_loss_rate"
Const QuoteLineDetailsCadFile As String = "cad_file"
Const QuoteLineDetailsPicture As String = "picture"
Const QuoteLineDetailsComment As String = "internal_comment"

Const TaskLabel As String = "label"
Const TaskOrdre As String = "ordre"
Const TaskQuoteLineId As String = "quote_lines_id"
Const TaskServiceId As String = "methods_services_id"
Const TaskSettingTime As String = "seting_time"
Const TaskUnitTime As String = "unit_time"
Const TaskStatuId As String = "status_id"
Const TaskType As String = "type"
Const TaskQty As String = "qty"
Const TaskUnitCost As String = "unit_cost"
Const TaskUnitPrice As String = "unit_price"
Const TaskMethodsUnitsId As String = "methods_units_id"

Dim messages As New Dictionary(Of Integer, String)
Dim MyCon As New Odbc.OdbcConnection
'MyCon.ConnectionString = "Driver={PostgreSQL ANSI};database=" & DatabaseName & ";server=" & ServerIp & ";port=" & ServerPort & ";uid=" & DatabaseUser & ";pwd=" & DatabasePassword & ";"
MyCon.ConnectionString = "Driver={MySQL ODBC 8.4 ANSI Driver};DATABASE=" & DatabaseName & ";SERVER=" & ServerIp & ";PORT=" & ServerPort & ";UID=" & DatabaseUser & ";PASSWORD=" & DatabasePassword & ";"

Dim ToSqlString As Func(of String, String) = _
	Function(s As String) As String
		If Not String.IsNullOrWhiteSpace(s) Then
			s = s.Replace("'","''")
		End If
		Return s
	End Function

'GET QUOTE REFERENCE
Dim GetQuoteReference As Func(Of String) =
    Function() As String
        Dim detail As RadQuote.Configuration.ServerParameters.Details.Detail = QUOTE.Details.FirstOrDefault(Function(d) String.Equals(d.ExternalId, "REFERENCE"))
        Dim reference As String
        If detail Is Nothing Then
            reference = ""
        Else
            reference = detail.Value.Value
            If detail.IsRichComment Then
                'On doit remplacer les retours chariots
                reference = reference.Replace(System.Environment.NewLine, "|")
            End If
        End If
        Return reference
    End Function

'GET QUOTE COMMENT
Dim GetQuoteComment As Func(Of String) =
    Function() As String
        Dim detail As RadQuote.Configuration.ServerParameters.Details.Detail = QUOTE.Details.FirstOrDefault(Function(d) String.Equals(d.ExternalId, "COMMENT"))
        Dim commment As String
        If detail Is Nothing Then
            commment = ""
        Else
            commment = detail.Value.Value
            If detail.IsRichComment Then
                'On doit remplacer les retours chariots
                commment = commment.Replace(System.Environment.NewLine, "|")
            End If
        End If
        Return commment
    End Function

'FUNCTION FOR ADD OPERTATION TO QUOTE LINE
Dim CreatePartOperation As Action(Of RadQuote.Business.Operations.Overview.OperationResultsOnPart, integer, integer) =
    Sub(op As RadQuote.Business.Operations.Overview.OperationResultsOnPart, QuoteLineId As integer, MethodsUnitId As integer)
		Dim operationId As Integer
		Dim operationType As Integer
		Dim operationGetterQueryString As String = "SELECT * FROM " & MethodsServicesTable
    	Dim operationGetterCommand As New Odbc.OdbcCommand(operationGetterQueryString, MyCon)
    	Dim operationGetterReader As Odbc.OdbcDataReader = operationGetterCommand.ExecuteReader()

    	While operationGetterReader.Read()
			If operationGetterReader("code").ToString().Trim() = op.OperationDefinition.ExternalId Then
        		operationId = operationGetterReader("id")
        		operationType = operationGetterReader("type")
			End If
    	End While
    	operationGetterReader.Close()

		'Type service
		' 1 = Productive
		' 2 = Raw material
		' 3 = Raw material (Sheet)
		' 4 = Raw material (Profil)
		' 5 = Raw material (block)
		' 6 = Supplies
		' 7 = Sub-contracting
		' 8 = Composed component
		If operationId <> 0 AND operationType = 1 Then
			Dim operationQueryString As String = "INSERT INTO " & TasksTable & "(" & _
																					TaskLabel & "," & _
																					TaskOrdre & "," & _
																					TaskQuoteLineId & "," & _
																					TaskServiceId & "," & _
																					TaskStatuId & "," & _
																					TaskType & "," & _
																					TaskQty & "," & _
																					TaskSettingTime & "," & _
																					TaskUnitTime & "," & _
																					TaskUnitCost & "," & _
																					TaskUnitPrice & "," & _
																					TaskMethodsUnitsId & ")" & _
																					" VALUES ('" & _
																					op.OperationDefinition.Name & "','" & _
																					op.OperationDefinition.Index & "','" & _
																					QuoteLineId & "','" & _
																					operationId & "','1','" & _
																					operationType & "','1','" & _
																					Math.Round(op.FullOtherTotalTime/100,2).ToString().Replace(",",".") & "','" & _
																					Math.Round(op.FullUnitProductTime/100,2).ToString().Replace(",",".") & "','" & _
																					Math.Round(op.UnitCost,2).ToString().Replace(",",".") & "','" & _
																					Math.Round(op.UnitPrice,2).ToString().Replace(",",".") & "','" & _
																					MethodsUnitId & "')"
																					
																					
    		Dim operationCommand As New Odbc.OdbcCommand(operationQueryString, MyCon)
			operationCommand.ExecuteNonQuery()
		End If
	End Sub

'FUNCTION OR CREATE QUOTE LINE
Dim CreateQuotePart As Action(Of RadQuote.Business.Parts.PartLine, integer, integer, integer, integer) =
    Sub(p As RadQuote.Business.Parts.PartLine, quoteId As integer, partId As integer , MethodsUnitId As integer, VatId As integer)
		Dim QuoteLineId As Integer
		Dim thickness As Decimal = 0
		Dim Material As string = ""
		
		If TypeOf(p.Part) Is RadQuote.Business.Parts.SymbolPart Then
			thickness = RadQuote.Business.Materials.Materials.Current.GetMaterial(CType(p.Part, RadQuote.Business.Parts.SymbolPart).MaterialId).Thicknesses.GetTechnology(CType(p.Part, RadQuote.Business.Parts.SymbolPart).ThicknessId).Description
			Material = ""
		Else
			thickness = 0
			Material = ""
		End If
		
		Dim partQueryString As String = "INSERT INTO " & QuoteLinesTable & "(" & _
																				QuoteLineQuoteId & "," & _
																				QuoteLineOrdre & "," & _
																				QuoteLineCode & "," & _
																				QuoteLineLabel & "," & _
																				QuoteLineQty & "," & _
																				QuoteLineMethodsUnitsId & "," & _
																				QuoteLineSellingPrice & "," & _
																				QuoteLineAccountingVatsId & "," & _
																				CreatedAt  & "," & _
																				UpdatedAt &")" & _
																				" VALUES ('" & _
																				quoteId & "','" & _
																				partId & "','" & _
																				p.Part.ID & "','" & _
																				p.Part.Names(0).ToString() & "','" & _
																				p.Part.Quantity & "'," & _
																				MethodsUnitId & ",'" & _ 
																				p.Part.SoldUnitPrice.ToString().Replace(",",".") & "'," & _
																				VatId & ",'" & _
																				Now().ToString("yyyy-MM-dd HH:mm:ss") & "','" & _
																				Now().ToString("yyyy-MM-dd HH:mm:ss") & "')"
    	Dim partCommand As New Odbc.OdbcCommand(partQueryString, MyCon)
		partCommand.ExecuteNonQuery()
        
		'Get last ID
		Dim scopeIdentityQuery As String = "SELECT LAST_INSERT_ID()"
		Dim identityCommand As New Odbc.OdbcCommand(scopeIdentityQuery, MyCon)
		QuoteLineId  = Convert.ToInt32(identityCommand.ExecuteScalar())
		
		messages.Add(QuoteLineId ,"Traitement de l'élément " & p.Part.Names(0).ToString() & " réussi.")
		
		Dim partDetailQueryString As String = "INSERT INTO " & QuoteLinesDetailsTable & "(" & _
																				QuoteLineDetailsQuoteId & "," & _
																				QuoteLineDetailsXsize & "," & _
																				QuoteLineDetailsYsize & "," & _
																				QuoteLineDetailsZsize & "," & _
																				QuoteLineDetailsMaterial & "," & _
																				QuoteLineDetailsThickness & "," & _
																				QuoteLineDetailsWeight & "," & _
																				QuoteLineDetailsComment & "," & _
																				CreatedAt  & "," & _
																				UpdatedAt &")" & _
																				" VALUES ('" & _
																				QuoteLineId & "'," & _
																				p.Part.X.ToString().Replace(",",".") & "," & _
																				p.Part.Y.ToString().Replace(",",".") & "," & _
																				p.Part.Z.ToString().Replace(",",".") & ",'" & _
																				Material & "'," & _
																				thickness.ToString().Replace(",",".") & "," & _
																				p.Part.Weight.ToString().Replace(",",".") & ",'" & _
																				ToSqlString(p.Part.Comment) & "','" & _
																				Now().ToString("yyyy-MM-dd HH:mm:ss") & "','" & _
																				Now().ToString("yyyy-MM-dd HH:mm:ss") & "')"
																				
    	Dim partDetailCommand As New Odbc.OdbcCommand(partDetailQueryString, MyCon)
		partDetailCommand.ExecuteNonQuery()
		
		For Each op As RadQuote.Business.Operations.Result.OperationResult In p.Part.OperationCalculations
            If op.IsUsedInCalculations _
                AndAlso TypeOf (op.OperationDefinition) Is RadQuote.Business.Operations.PartOperation Then			
				Dim OperationResults As RadQuote.Business.Operations.Overview.OperationResultsOnPart =
            		p.OperationsResults.FirstOrDefault(Function(op2) op2.OperationDefinition.Name = op.OperationDefinition.Name)
				CreatePartOperation(OperationResults, QuoteLineId, MethodsUnitId)
			End If
		Next
		
		For Each sp As RadQuote.Business.Parts.PartLine In p.SubParts
			CreateQuotePart(sp, quoteId, partId, MethodsUnitId, VatId)
        Next
	End Sub

MyCon.Open()
Dim ContinueProcess As Boolean = True
If MyCon.State = ConnectionState.Open Then

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

	'READ METHODS UNIT INFORMATION FROM WEM TABLE
	Dim MethodsUnitQueryString As String = "SELECT * FROM " & MethodsUnitsTable & " WHERE " & "code" & "='" & MethodsUnitCode & "'" 
    Dim MethodsUnitCommand As New Odbc.OdbcCommand(MethodsUnitQueryString, MyCon)
    Dim MethodsUnitReader As Odbc.OdbcDataReader = MethodsUnitCommand.ExecuteReader()
    While MethodsUnitReader.Read()
		MethodsUnitId = MethodsUnitReader(0)
    End While
    MethodsUnitReader.Close()

	'READ ACCOUNTING VAT INFORMATION FROM WEM TABLE
	Dim AccountingVatQueryString As String = "SELECT * FROM " & AccountingVatsTable & " WHERE " & "code" & "='" & VATCode & "'" 
    Dim AccountingVatCommand As New Odbc.OdbcCommand(AccountingVatQueryString, MyCon)
    Dim AccountingVatReader As Odbc.OdbcDataReader = AccountingVatCommand.ExecuteReader()
    While AccountingVatReader.Read()
		VatId = AccountingVatReader(0)
    End While
    AccountingVatReader.Close()


	'READ CUSTOMER INFORMATION FROM WEM TABLE
	Dim customerSiteQueryString As String = "SELECT * FROM " & CompaniesTable & " WHERE " & "code" & "='" & QUOTE.Site.ExternalId & "'" 
    Dim customerSiteCommand As New Odbc.OdbcCommand(customerSiteQueryString, MyCon)
    Dim customerSiteReader As Odbc.OdbcDataReader = customerSiteCommand.ExecuteReader()
    While customerSiteReader.Read()
		customerId = customerSiteReader(0)
    End While
    customerSiteReader.Close()

	'READ ADDRESS INFORMATION FROM WEM TABLE
	Dim addressQueryString As String = "SELECT * FROM " & CompanieAddressesTable & " WHERE " & "companies_id" & "='" & customerId & "'" 
    Dim addressCommand As New Odbc.OdbcCommand(addressQueryString, MyCon)
    Dim addressReader As Odbc.OdbcDataReader = addressCommand.ExecuteReader()
    While addressReader.Read()
        If addressReader("adress").ToString().Trim() = QUOTE.Site.Address AndAlso addressReader("ZipCode").ToString().Trim() = QUOTE.Site.Postcode AndAlso addressReader("city").ToString().Trim() = QUOTE.Site.City Then
			customerAddressId = addressReader("id")
		End If
    End While
    addressReader.Close()

	'READ CONTACT INFORMATION FROM WEM TABLE
	Dim contactQueryString As String = "SELECT * FROM " & CompaniesContactTable & " WHERE " & "companies_id" & "='" & customerId & "'" 
    Dim contactCommand As New Odbc.OdbcCommand(contactQueryString, MyCon)
    Dim contactReader As Odbc.OdbcDataReader = contactCommand.ExecuteReader()
    While contactReader.Read()
        If contactReader("first_name").ToString().Trim() = QUOTE.Contact.Surname AndAlso contactReader("name").ToString().Trim() = QUOTE.Contact.Forename Then
			customerContactId = contactReader("id")
		End If
    End While
    contactReader.Close()

	'READ USER INFORMATION FROM WEM TABLE
	Dim userGetterCreatorQueryString As String = "SELECT * FROM " & UserTable & " WHERE " & "name" & "='" & Quote.Creator.OtherInformation & "'" 
	Dim userCreatorGetterCommand As New Odbc.OdbcCommand(userGetterCreatorQueryString, MyCon)
	Dim userCreatorGetterReader As Odbc.OdbcDataReader = userCreatorGetterCommand.ExecuteReader()
	While userCreatorGetterReader.Read()
		creationUserId = userCreatorGetterReader(0)
	End While
	userCreatorGetterReader.Close()

	'READ PAYMENT CONDITION INFORMATION FROM WEM TABLE
	
	Dim paymentConditionQueryString As String = "SELECT * FROM " & PaymentConditionsTable & " WHERE " & "CODE" & "='" & PaymentConditionsCode & "'" 
	Dim paymentConditionCommand As New Odbc.OdbcCommand(paymentConditionQueryString, MyCon)
	Dim paymentConditionReader As Odbc.OdbcDataReader = paymentConditionCommand.ExecuteReader()
	While paymentConditionReader.Read()
		PaymentConditionsId = paymentConditionReader("id")
	End While
	paymentConditionReader.Close()
	
	'READ PAYMENT METHODS INFORMATION FROM WEM TABLE
	Dim paymentMethodsQueryString As String = "SELECT * FROM " & PaymentMethodsTable & " WHERE " & "CODE" & "='" & PaymentMethodsCode & "'" 
	Dim paymentMethodsCommand As New Odbc.OdbcCommand(paymentMethodsQueryString, MyCon)
	Dim paymentMethodsReader As Odbc.OdbcDataReader = paymentMethodsCommand.ExecuteReader()
	While paymentMethodsReader.Read()
		PaymentMethodsId = paymentMethodsReader("id")
	End While
	paymentMethodsReader.Close()
	
	'READ PAYMENT DELIVERIES INFORMATION FROM WEM TABLE
	Dim deliveriesQueryString As String = "SELECT * FROM " & DeliveriesTable & " WHERE " & "CODE" & "='" & DeliveriesCode & "'"  
	Dim deliveriesCommand As New Odbc.OdbcCommand(deliveriesQueryString, MyCon)
	Dim deliveriesReader As Odbc.OdbcDataReader = deliveriesCommand.ExecuteReader()
	While deliveriesReader.Read()
		DeliveriesId = deliveriesReader("id")
	End While
	deliveriesReader.Close()
	
	Dim QuoteGuid As Guid = Guid.NewGuid()
	Dim QuoteGuidAsString As String = QuoteGuid.ToString()

	'CREATE QUOTE
	Dim quoteQueryString As String = "INSERT INTO " & QuoteTable & "(" & _
																	QuoteUUID & "," & _
																	QuoteCode & "," & _
																	QuoteLabel & "," & _
																	QuoteReference & "," & _
																	QuoteCustomerId & "," & _
																	QuoteCustomerContactId & "," & _
																	QuoteCustomerAddressId & "," & _
																	QuoteValidityDate & "," & _
																	QuoteUserId & "," & _
																	QuotePaymentConditionsId & "," & _
																	QuotePaymentMethodsId & "," & _
																	QuoteDeliveriesId & "," & _
																	QuoteComment  & "," & _
																	CreatedAt  & "," & _
																	UpdatedAt &")" & _
																	" VALUES ('" & _
																	QuoteGuidAsString & "','" & _
																	ToSqlString(Quote.Name) & "','" & _
																	ToSqlString(Quote.Name) & "','" & _
																	ToSqlString(GetQuoteReference()) & "'," & _
																	customerId & "," & _
																	customerContactId & "," & _
																	customerAddressId & ",'" & _
																	Now().ToString("yyyy-MM-dd") & "'," & _
																	creationUserId & "," & _
																	PaymentConditionsId & "," & _
																	PaymentMethodsId & "," & _
																	DeliveriesId & ",'" & _
																	ToSqlString(GetQuoteComment()) & "','" & _
																	Now().ToString("yyyy-MM-dd HH:mm:ss") & "','" & _
																	Now().ToString("yyyy-MM-dd HH:mm:ss") & "')"
		
	Dim quoteCommand As New Odbc.OdbcCommand(quoteQueryString, MyCon)
	quoteCommand.ExecuteNonQuery()

	
	messages.Add(0,"Traitement du devis " & ToSqlString(Quote.Name) & " réussi.")
        
	'Get last ID
	Dim scopeIdentityQuery As String = "SELECT LAST_INSERT_ID()"
	Dim identityCommand As New Odbc.OdbcCommand(scopeIdentityQuery, MyCon)
	quoteId  = Convert.ToInt32(identityCommand.ExecuteScalar())
	
	For Each p As RadQuote.Business.Parts.PartLine In QUOTE.Parts.SubParts				
		CreateQuotePart(p, quoteId, 0, MethodsUnitId, VatId)
	Next
	
End If
MyCon.Close()

If ContinueProcess Then
	Dim resultMessage As String = ""
	For Each kvp As KeyValuePair(Of Integer, String) In messages
		resultMessage &= kvp.Value & vbCrLf
	Next

	RadWin.ShowMsgBox("Export", "Résultats des traitements :" & vbCrLf & resultMessage)
End If  