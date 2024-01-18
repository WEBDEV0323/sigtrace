<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
  <html>
  <body style="margin:0 auto">
    <center><h1>Unit Test Report for build #{{BUILDNUMBER}}</h1></center>   
	<xsl:for-each select="testsuites/testsuite/testsuite">
      <h2><xsl:value-of select="@name"/></h2>
	  <table border="1">
      <tr bgcolor="#9acd32">
        <th>Test Name</th>
        <th>Class Name</th>
		<th>File</th>
		<th>Line</th>
		<th>Assertion</th>
		<th>Time</th>
		<th>Description</th>
		<th>Date of execution</th>
		<th>Status</th>
      </tr>
      <xsl:for-each select="testcase">
        <tr>
          <td><xsl:value-of select="@name"/></td>
          <td><xsl:value-of select="@class"/></td>
		  <td><xsl:value-of select="@file"/></td>
		  <td><xsl:value-of select="@line"/></td>
		  <td><xsl:value-of select="@assertions"/></td>
		  <td><xsl:value-of select="@time"/></td>
		  <td><xsl:value-of select="@DLR"/></td>
		  <td><xsl:value-of  select="@Date_of_execution"/></td>
	      <td><!--xsl:if test="failure">
              <b>Failed !</b>
              <i><xsl:value-of select="*"/></i>
		  </xsl:if-->
		  <xsl:choose>
            <xsl:when test="failure">
                <b>Failed !</b>
              <!--i><xsl:value-of select="*"/></i-->
            </xsl:when>
			<xsl:when test="error">
                <b>Failed !</b>
              <i><xsl:value-of select="*"/></i>
            </xsl:when>
            <xsl:otherwise>
                <b>Pass</b>
            </xsl:otherwise>
        </xsl:choose>
		</td>
        </tr>
      </xsl:for-each>
    </table>
    </xsl:for-each>
  </body>
  </html>
</xsl:template>
</xsl:stylesheet>