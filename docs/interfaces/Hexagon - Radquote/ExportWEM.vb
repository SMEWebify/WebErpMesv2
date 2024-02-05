'Variables from RADQuote Constants
Dim ServerIp As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Serveur_IP","wem").Value.StringValue
Dim ServerPort As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Serveur_port","wem").Value.StringValue
Dim DatabaseName As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Nom_base","wem").Value.StringValue
Dim DatabaseUser As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Utilisateur","wem").Value.StringValue
Dim DatabasePassword As String = RadQuote.Business.QuoteConstants.QuoteConstants.Current.GetQuoteConstant("qc_wem_Mot_de_passe","wem").Value.StringValue


'Database table names
Const QuoteTable As String = """quotes"""
Const QuoteLinesTable As String = """quote_lines"""
Const QuoteLinesDetailsTable As String = """quote_line_details"""
Const TasksTable As String = """tasks"""
Const SubAssembliesTable As String = """sub_assemblies"""
Const UserTable As String = """users"""
Const CompaniesTable As String = """companies"""
Const CompanieAddressesTable As String = """companies_addresses"""
Const CompaniesContactTable As String = """companies_contacts"""

'Database column names
Const QuoteUUID As String = """uuid"""
Const QuoteCode As String = """code"""
Const QuoteLabel As String = """label"""
Const QuoteReference As String = """customer_reference"""
Const QuoteCustomerId As String = """companies_id"""
Const QuoteCustomerContactId As String = """companies_contacts_id"""
Const QuoteCustomerAddressId As String = """companies_addresses_id"""
Const QuoteValidityDate As String = """validity_date"""
Const QuoteUserId As String = """user_id"""
Const QuotePaymentConditionsId As String = """accounting_payment_conditions_id"""
Const QuotePaymentMethodsId As String = """accounting_payment_methods_id"""
Const QuoteDeliveriesId As String = """accounting_deliveries_id"""
Const QuoteComment As String = """comment"""

Const QuoteLineQuoteId As String = """quotes_id"""
Const QuoteLineOrdre As String = """ordre"""
Const QuoteLineCode As String = """code"""
Const QuoteLineProductId As String = """product_id"""
Const QuoteLineLabel As String = """label"""
Const QuoteLineQty As String = """qty"""
Const QuoteLineMethodsUnitsId As String = """methods_units_id"""
Const QuoteLineSellingPrice As String = """selling_price"""
Const QuoteLineDiscount As String = """discount"""
Const QuoteLineAccountingVatsId As String = """accounting_vats_id"""
Const QuoteLineDeliveryDate As String = """delivery_date"""

Const QuoteLineDetailsQuoteId As String = """quote_lines_id"""
Const QuoteLineDetailsXsize As String = """x_size"""
Const QuoteLineDetailsYsize As String = """y_size"""
Const QuoteLineDetailsZsize As String = """z_size"""
Const QuoteLineDetailsXoversizesize As String = """x_oversize"""
Const QuoteLineDetailsYoversizesize As String = """y_oversize"""
Const QuoteLineDetailsZoversizesize As String = """z_oversize"""
Const QuoteLineDetailsDiameter As String = """diameter"""
Const QuoteLineDetailsMaterial As String = """material"""
Const QuoteLineDetailsThickness As String = """thickness"""
Const QuoteLineDetailsWeight As String = """weight"""
Const QuoteLineDetailsMaterialLossRate As String = """material_loss_rate"""
Const QuoteLineDetailsCadFile As String = """cad_file"""
Const QuoteLineDetailsPicture As String = """picture"""

Const TaskLabel As String = """label"""
Const TaskOrdre As String = """ordre"""
Const TaskQuoteLineId As String = """quote_lines_id"""
Const TaskServiceId As String = """methods_services_id"""
Const TaskSettingTime As String = """seting_time"""
Const TaskUnitTime As String = """unit_time"""
Const TaskStatuId As String = """status_id"""
Const TaskType As String = """type"""
Const TaskQty As String = """qty"""
Const TaskUnitCost As String = """unit_cost"""
Const TaskUnitPrice As String = """unit_price"""
Const TaskMethodsUnitsId As String = """methods_units_id"""

Dim MyCon As New Odbc.OdbcConnection
MyCon.ConnectionString = "Driver={PostgreSQL ANSI};database=" & DatabaseName & ";server=" & ServerIp & ";port=" & ServerPort & ";uid=" & DatabaseUser & ";pwd=" & DatabasePassword & ";"

Dim ToSqlString As Func(of String, String) = _
	Function(s As String) As String
		If Not String.IsNullOrWhiteSpace(s) Then
			s = s.Replace("'","''")
		End If
		Return s
	End Function

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
	
MyCon.Open()
Dim ContinueProcess As Boolean = True
If MyCon.State = ConnectionState.Open Then


	Dim quoteId As Integer
	Dim customerId As Integer
	Dim customerAddressId As Integer
	Dim customerContactId As Integer
	Dim PaymentConditionsId As Integer
	Dim PaymentMethodsId As Integer
	Dim DeliveriesId As Integer
	
	
	
	'READ CUSTOMER INFORMATION FROM WEM TABLE
	Dim customerSiteQueryString As String = "SELECT * FROM " & CompaniesTable & " WHERE " & """code""" & "='" & QUOTE.Site.ExternalId & "'" 
    Dim customerSiteCommand As New Odbc.OdbcCommand(customerSiteQueryString, MyCon)
    Dim customerSiteReader As Odbc.OdbcDataReader = customerSiteCommand.ExecuteReader()
	
    While customerSiteReader.Read()
		customerId = customerSiteReader(1)
    End While
    customerSiteReader.Close()
	
	'READ ADDRESS INFORMATION FROM WEM TABLE
	Dim addressQueryString As String = "SELECT * FROM " & CompanieAddressesTable & " WHERE " & """companies_id""" & "='" & customerSiteId & "'" 
    Dim addressCommand As New Odbc.OdbcCommand(addressQueryString, MyCon)
    Dim addressReader As Odbc.OdbcDataReader = addressCommand.ExecuteReader()

    While addressReader.Read()
        If addressReader(2).ToString().Trim() = QUOTE.Site.Address AndAlso addressReader(3).ToString().Trim() = QUOTE.Site.Postcode AndAlso addressReader(4).ToString().Trim() = QUOTE.Site.City Then
			customerAddressId = addressReader(0)
		End If
    End While
    addressReader.Close()
	
	'READ CONTACT INFORMATION FROM WEM TABLE
	Dim contactQueryString As String = "SELECT * FROM " & CompaniesContactTable & " WHERE " & """companies_id""" & "='" & customerSiteId & "'" 
    Dim contactCommand As New Odbc.OdbcCommand(contactQueryString, MyCon)
    Dim contactReader As Odbc.OdbcDataReader = contactCommand.ExecuteReader()

    While contactReader.Read()
        If contactReader(3).ToString().Trim() = QUOTE.Contact.Surname AndAlso contactReader(4).ToString().Trim() = QUOTE.Contact.Forename Then
			customerContactId = contactReader(0)
		End If
    End While
    contactReader.Close()
	
	'READ USER INFORMATION FROM WEM TABLE
	Dim creationUserId As Integer
	Dim userGetterCreatorQueryString As String = "SELECT * FROM " & UserTable & " WHERE " & """name""" & "='" & Quote.Creator.OtherInformation & "'" 
	Dim userCreatorGetterCommand As New Odbc.OdbcCommand(userGetterCreatorQueryString, MyCon)
	Dim userCreatorGetterReader As Odbc.OdbcDataReader = userCreatorGetterCommand.ExecuteReader()

	While userCreatorGetterReader.Read()
		creationUserId = userCreatorGetterReader(0)
	End While
	userCreatorGetterReader.Close()

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
		QuoteComment & ")" & _
		" VALUES ('" & _
		Guid.NewGuid() & "','" & _
		Quote.Name & "','" & _
		Quote.Name & "','" & _
		ToSqlString(GetQuoteReference()) & "','" & _
		QuoteCustomerId & "," & _
		QuoteCustomerContactId & "," & _
		QuoteCustomerAddressId & "," & _
		Now() & "," & _
		QuoteUserId & "," & _
		QuotePaymentConditionsId & "," & _
		QuotePaymentMethodsId & "," & _
		QuoteDeliveriesId & "," & _
		ToSqlString(GetQuoteComment()) & "')"
		
		
	Dim quoteCommand As New Odbc.OdbcCommand(quoteQueryString, MyCon)
	quoteCommand.ExecuteNonQuery()


	
End If
MyCon.Close()

If ContinueProcess Then
	RadWin.ShowMsgBox("Export","Export terminé")
End If